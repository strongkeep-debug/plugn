const fs = require("fs");
const path = require("path");

const cronControllerPath = path.join(__dirname, "..", "console", "controllers", "CronController.php");
const source = fs.readFileSync(cronControllerPath, "utf8");

function functionBody(name) {
  const marker = `public function ${name}`;
  const start = source.indexOf(marker);
  if (start === -1) {
    throw new Error(`${name} was not found in CronController.php`);
  }

  const openBrace = source.indexOf("{", start);
  if (openBrace === -1) {
    throw new Error(`${name} has no opening brace`);
  }

  let depth = 0;
  for (let index = openBrace; index < source.length; index++) {
    const character = source[index];

    if (character === "{") {
      depth++;
    } else if (character === "}") {
      depth--;
    }

    if (depth === 0) {
      return source.slice(openBrace + 1, index);
    }
  }

  throw new Error(`${name} has no closing brace`);
}

const checkedActions = [
  "actionFixPlatformFee",
  "actionDowngradedStoreSubscription",
  "actionCreateBuildJsFile",
];

const blockedPatterns = [
  { label: "print_r", pattern: /print_r\s*\(/ },
  { label: "die", pattern: /\bdie\s*(?:\(|;)/ },
  { label: "exit", pattern: /\bexit\s*(?:\(|;)/ },
];

for (const action of checkedActions) {
  const body = functionBody(action);

  for (const blocked of blockedPatterns) {
    if (blocked.pattern.test(body)) {
      throw new Error(`${action} still contains ${blocked.label}`);
    }
  }
}

const requiredLogMessages = [
  "Cron failed to reset platform fee.",
  "Cron failed to deactivate downgraded subscription.",
  "Cron failed to restore platform fee after subscription downgrade.",
  "Cron failed to create build js file.",
];

for (const message of requiredLogMessages) {
  if (!source.includes(message)) {
    throw new Error(`Missing cron failure log message: ${message}`);
  }
}

if (!source.includes("Queue::QUEUE_STATUS_FAILED")) {
  throw new Error("Build queue failure status is not preserved");
}

console.log("PASS CronController cron failure paths log and continue without print_r/die/exit");
