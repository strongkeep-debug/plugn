from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
controller = ROOT / "backend" / "controllers" / "AgentAssignmentController.php"
source = controller.read_text(encoding="utf-8")


def require(condition, message):
    if not condition:
        raise SystemExit(message)


require(
    "Unable to save agent assignment. Please review the form and try again." in source,
    "Agent assignment validation/save failures should show a generic flash message",
)
require(
    "Selected agent could not be found. Please choose another agent." in source,
    "Missing agent assignments should show a clear user-facing flash message",
)
require(
    "Failed to save agent assignment." in source,
    "Agent assignment save failures should be logged for operators",
)
require(
    "'errors' => $model->errors" in source,
    "Agent assignment failure logging should preserve model errors for operators",
)
require(
    "Yii::error(json_encode([" in source,
    "Agent assignment failure logging should serialize structured error details",
)
require(
    "setFlash('error', print_r($model->errors, true))" not in source
    and "setFlash('error', var_dump($model->errors" not in source
    and "setFlash('error', json_encode($model->errors" not in source
    and "setFlash('error', serialize($model->errors" not in source,
    "Agent assignment flashes should not expose raw model errors",
)

print("PASS agent assignment failures log details without flashing raw model errors")
