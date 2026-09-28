const fs = require("fs");
const path = require("path");

const PATCH_MARKER = "delete message.__x_id;";

function applyPatch(targetPath) {
  const source = fs.readFileSync(targetPath, "utf8");

  if (source.includes(PATCH_MARKER)) {
    return false;
  }

  const messageStart = source.indexOf("const message = {");
  const nextSection = source.indexOf("// Bot's won't reply", messageStart);

  if (messageStart < 0 || nextSection < 0) {
    throw new Error(`Unsupported whatsapp-web.js Utils.js layout: ${targetPath}`);
  }

  const messageBlock = source.slice(messageStart, nextSection);
  if (!messageBlock.includes("...mediaOptions")) {
    throw new Error(`Unable to locate media options in whatsapp-web.js: ${targetPath}`);
  }

  const patched =
    source.slice(0, nextSection) +
    "// Prevent MediaData.__x_id from replacing the outgoing MsgKey.\n" +
    "        delete message.__x_id;\n\n        " +
    source.slice(nextSection);

  fs.writeFileSync(targetPath, patched, "utf8");
  return true;
}

if (require.main === module) {
  const targetPath = path.join(
    __dirname,
    "node_modules",
    "whatsapp-web.js",
    "src",
    "util",
    "Injected",
    "Utils.js"
  );

  const changed = applyPatch(targetPath);
  process.stdout.write(
    changed
      ? "Applied whatsapp-web.js media ID collision fix.\n"
      : "whatsapp-web.js media ID collision fix already applied.\n"
  );
}

module.exports = { applyPatch, PATCH_MARKER };
