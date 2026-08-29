const whatsappWebJs = require("whatsapp-web.js");

function isDuplicateBindingError(error) {
  const message = String(error?.message || error || "").toLowerCase();

  return (
    message.includes("already exists") &&
    (message.includes("page binding") ||
      message.includes("window['") ||
      message.includes("onqrcodevent") ||
      message.includes("onqrchangedevent") ||
      message.includes("onaddmessageevent"))
  );
}

function patchExposeFunctionIfAbsent() {
  try {
    const puppeteerUtil = require("whatsapp-web.js/src/util/Puppeteer");

    if (puppeteerUtil.__usbCrmExposePatchApplied) {
      return;
    }

    const originalExposeFunctionIfAbsent = puppeteerUtil.exposeFunctionIfAbsent;
    puppeteerUtil.exposeFunctionIfAbsent = async function patchedExposeFunctionIfAbsent(page, name, fn) {
      try {
        return await originalExposeFunctionIfAbsent(page, name, fn);
      } catch (error) {
        if (isDuplicateBindingError(error)) {
          return;
        }

        throw error;
      }
    };

    puppeteerUtil.__usbCrmExposePatchApplied = true;
  } catch (_) {
    // If the internal path changes in a future library release, the client patch below
    // still protects the common concurrent inject race.
  }
}

function patchClientInject(Client) {
  if (!Client || Client.prototype.__usbCrmInjectPatchApplied) {
    return;
  }

  const originalInject = Client.prototype.inject;

  async function attachDiagnostics(client) {
    if (client.__usbCrmDiagnosticsAttached || !client.pupPage) {
      return;
    }

    client.__usbCrmDiagnosticsAttached = true;
    client.pupPage.on("console", (msg) => {
      const type = typeof msg.type === "function" ? msg.type() : "log";
      if (type === "error" || type === "warning") {
        Promise.resolve(msg.text?.()).then((text) => {
          console.log(`[WA:${type}] ${text}`);
        }).catch(() => {});
      }
    });

    client.pupPage.on("pageerror", (error) => {
      console.log(`[WA:pageerror] ${String(error?.message || error)}`);
    });
  }

  Client.prototype.inject = async function patchedInject(...args) {
    if (this.__usbCrmInjectPromise) {
      return this.__usbCrmInjectPromise;
    }

    if (this.__usbCrmInjectedStable && this.pupPage && !this.lastLoggedOut) {
      await attachDiagnostics(this);
      return this.info || null;
    }

    const run = (async () => {
      try {
        await attachDiagnostics(this);
        const result = await originalInject.apply(this, args);
        this.__usbCrmInjectedStable = true;
        return result;
      } finally {
        this.__usbCrmInjectPromise = null;
      }
    })();

    this.__usbCrmInjectPromise = run;
    return run;
  };

  Client.prototype.__usbCrmInjectPatchApplied = true;
}

patchExposeFunctionIfAbsent();
patchClientInject(whatsappWebJs.Client);

module.exports = whatsappWebJs;
