const { LocalAuth } = require("whatsapp-web.js");

// The manager owns logout cleanup: close Chromium, then archive the profile.
// Upstream LocalAuth deletes a live browser profile on a logout navigation;
// on Windows this partially deletes it before failing on locked files.
class SessionAuth extends LocalAuth {
  async logout() {
    this.logoutRequested = true;
  }

  async beforeBrowserInitialized() {
    if (!this.logoutRequested) {
      await super.beforeBrowserInitialized();
    }
  }
}

module.exports = SessionAuth;
