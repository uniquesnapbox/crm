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
