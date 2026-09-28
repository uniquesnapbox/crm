const assert = require("node:assert/strict");
const fs = require("node:fs");
const os = require("node:os");
const path = require("node:path");
const test = require("node:test");

const { applyPatch, PATCH_MARKER } = require("../patch-whatsapp-web");

test("patch removes the colliding media model ID and is idempotent", () => {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), "wwebjs-patch-"));
  const target = path.join(directory, "Utils.js");

  fs.writeFileSync(
    target,
    [
      "const message = {",
      "  id: newMsgKey,",
      "  ...mediaOptions,",
      "};",
      "",
      "// Bot's won't reply if canonicalUrl is set",
    ].join("\n"),
    "utf8"
  );

  try {
    assert.equal(applyPatch(target), true);
    assert.match(fs.readFileSync(target, "utf8"), new RegExp(PATCH_MARKER.replaceAll(".", "\\.")));
    assert.equal(applyPatch(target), false);
  } finally {
    fs.rmSync(directory, { recursive: true, force: true });
  }
});
