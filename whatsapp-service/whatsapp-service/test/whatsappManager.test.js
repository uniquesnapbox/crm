const assert = require("node:assert/strict");
const test = require("node:test");
const WhatsAppManager = require("../src/whatsappManager");

function createManager() {
  return new WhatsAppManager({
    defaultSession: "test-session",
    sessions: ["test-session"]
  });
}

test("isQrStale identifies expired QR codes", () => {
  const manager = createManager();
  manager.qrCode.set("test-session", "qr-value");
  manager.qrGeneratedAt.set("test-session", new Date(Date.now() - 120000).toISOString());

  assert.equal(manager.isQrStale("test-session"), true);

  manager.qrGeneratedAt.set("test-session", new Date().toISOString());
  assert.equal(manager.isQrStale("test-session"), false);
});

test("refreshQr recycles an unpaired client with an expired QR", async () => {
  const manager = createManager();
  manager.clients.set("test-session", {});
  manager.status.set("test-session", "qr_required");
  manager.qrCode.set("test-session", "expired-qr");
  manager.qrGeneratedAt.set("test-session", new Date(Date.now() - 120000).toISOString());

  let destroyCount = 0;
  let initCount = 0;
  manager.inspectClientReadiness = async () => ({ ready: false });
  manager.destroyClient = async (key) => {
    destroyCount += 1;
    manager.clients.delete(key);
    manager.qrCode.delete(key);
    manager.qrGeneratedAt.delete(key);
  };
  manager.initClient = async (key) => {
    initCount += 1;
    manager.clients.set(key, {});
    manager.status.set(key, "qr_required");
    manager.qrCode.set(key, "fresh-qr");
    manager.qrGeneratedAt.set(key, new Date().toISOString());
  };

  const result = await manager.refreshQr("test-session");

  assert.equal(destroyCount, 1);
  assert.equal(initCount, 1);
  assert.equal(result.qr, "fresh-qr");
});

test("refreshQr keeps a fresh QR and never restarts a ready session", async () => {
  const manager = createManager();
  let destroyCount = 0;
  manager.destroyClient = async () => {
    destroyCount += 1;
  };

  manager.clients.set("test-session", {});
  manager.status.set("test-session", "qr_required");
  manager.qrCode.set("test-session", "fresh-qr");
  manager.qrGeneratedAt.set("test-session", new Date().toISOString());
  manager.inspectClientReadiness = async () => ({ ready: false });
  await manager.refreshQr("test-session");
  assert.equal(destroyCount, 0);

  manager.status.set("test-session", "ready");
  await manager.refreshQr("test-session");
  assert.equal(destroyCount, 0);
});

test("refreshQr preserves a scanned client while WhatsApp is linking", async () => {
  const manager = createManager();
  const client = {};
  manager.clients.set("test-session", client);
  manager.status.set("test-session", "qr_required");
  manager.qrCode.set("test-session", "expired-qr");
  manager.qrGeneratedAt.set("test-session", new Date(Date.now() - 120000).toISOString());

  let destroyCount = 0;
  manager.destroyClient = async () => {
    destroyCount += 1;
  };
  manager.inspectClientReadiness = async () => ({
    ready: false,
    waState: "CONNECTED"
  });
  manager.scheduleReadyReconciliation = () => {};

  const result = await manager.refreshQr("test-session");

  assert.equal(destroyCount, 0);
  assert.equal(result.status, "authenticated");
  assert.equal(result.qr, null);
  assert.equal(manager.getClient("test-session"), client);
});

test("change_state preserves OPENING client and clears the scanned QR", () => {
  const manager = createManager();
  manager.scheduleReadyReconciliation = () => {};

  const client = manager.createClient("test-session");
  manager.clients.set("test-session", client);
  manager.status.set("test-session", "qr_required");
  manager.qrCode.set("test-session", "scanned-qr");
  manager.qrGeneratedAt.set("test-session", new Date().toISOString());

  client.emit("change_state", "OPENING");

  assert.equal(manager.getStatus("test-session"), "authenticated");
  assert.equal(manager.getQr("test-session"), null);
  assert.equal(manager.getClient("test-session"), client);
});

test("concurrent refresh requests share one stale QR restart", async () => {
  const manager = createManager();
  manager.clients.set("test-session", {});
  manager.status.set("test-session", "qr_required");
  manager.qrCode.set("test-session", "expired-qr");
  manager.qrGeneratedAt.set("test-session", new Date(Date.now() - 120000).toISOString());
  manager.inspectClientReadiness = async () => ({ ready: false });

  let destroyCount = 0;
  manager.destroyClient = async (key) => {
    destroyCount += 1;
    await new Promise((resolve) => setTimeout(resolve, 20));
    manager.clients.delete(key);
    manager.qrCode.delete(key);
    manager.qrGeneratedAt.delete(key);
  };
  manager.initClient = async (key) => {
    manager.clients.set(key, {});
    manager.status.set(key, "qr_required");
    manager.qrCode.set(key, "fresh-qr");
    manager.qrGeneratedAt.set(key, new Date().toISOString());
  };

  const [first, second] = await Promise.all([
    manager.refreshQr("test-session"),
    manager.refreshQr("test-session")
  ]);

  assert.equal(destroyCount, 1);
  assert.equal(first.qr, "fresh-qr");
  assert.equal(second.qr, "fresh-qr");
});

test("logout recovery closes browser before archiving and shares concurrent requests", async () => {
  const manager = createManager();
  const client = {};
  manager.clients.set("test-session", client);
  const steps = [];
  manager.destroyClient = async () => { steps.push("close"); };
  manager.quarantineLoggedOutSession = async () => { steps.push("archive"); };
  manager.initClient = async () => { steps.push("start"); };
  await Promise.all([
    manager.recoverLoggedOutClient("test-session", client, "logout"),
    manager.recoverLoggedOutClient("test-session", client, "logout")
  ]);
  assert.deepEqual(steps, ["close", "archive", "start"]);
  assert.equal(manager.logoutRecoveries.size, 0);
});

test("a logged-out old browser cannot reset its replacement", () => {
  const manager = createManager();
  const oldClient = manager.createClient("test-session");
  manager.clients.set("test-session", {});
  manager.status.set("test-session", "ready");
  oldClient.emit("disconnected", "LOGOUT");
  oldClient.emit("qr", "old-qr");
  oldClient.emit("auth_failure", "stale failure");
  assert.equal(manager.getStatus("test-session"), "ready");
  assert.equal(manager.getQr("test-session"), null);
  assert.equal(manager.logoutRecoveries.size, 0);
});

test("authentication removes the scanned QR", () => {
  const manager = createManager();
  manager.scheduleReadyReconciliation = () => {};
  const client = manager.createClient("test-session");
  manager.clients.set("test-session", client);
  manager.qrCode.set("test-session", "scanned-qr");
  client.emit("authenticated");
  assert.equal(manager.getStatus("test-session"), "authenticated");
  assert.equal(manager.getQr("test-session"), null);
});

test("CONNECTED alone is not ready until chat UI and message helpers are loaded", async () => {
  const manager = createManager();
  const page = { readyState: "complete", hasQr: false, hasChatList: false, hasHelpers: false };
  const client = { getState: async () => "CONNECTED", pupPage: { evaluate: async () => page } };
  assert.equal((await manager.inspectClientReadiness(client)).ready, false);
  page.hasChatList = true;
  page.hasHelpers = true;
  assert.equal((await manager.inspectClientReadiness(client)).ready, true);
});

test("logout defers auth profile cleanup until the manager closes the browser", async () => {
  const SessionAuth = require("../src/sessionAuth");
  const auth = new SessionAuth({ clientId: "test-session" });
  await auth.logout();
  assert.equal(auth.logoutRequested, true);
  // Upstream invokes this hook during logout navigation. It must not recreate
  // the profile or access the old browser while recovery is closing it.
  await auth.beforeBrowserInitialized();
});

test("readiness linking probes do not create a second reconciliation loop", () => {
  const manager = createManager();
  const client = {};
  manager.clients.set("test-session", client);
  let scheduled = 0;
  manager.scheduleReadyReconciliation = () => { scheduled += 1; };
  manager.preserveLinkingClient(
    "test-session",
    client,
    "readiness_probe",
    "CONNECTED",
    { page: { hasQr: false } },
    false
  );
  assert.equal(manager.getStatus("test-session"), "authenticated");
  assert.equal(scheduled, 0);
});
