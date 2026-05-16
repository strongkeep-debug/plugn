from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
model = ROOT / "partner" / "models" / "PasswordResetRequestForm.php"
source = model.read_text(encoding="utf-8")


def require(condition, message):
    if not condition:
        raise SystemExit(message)


require(
    "Failed to save partner password reset token." in source,
    "Partner password reset token save failures should be logged",
)
require(
    "'partner_uuid' => $partner->partner_uuid" in source and "'errors' => $partner->errors" in source,
    "Partner password reset logging should include partner id and model errors",
)
require(
    "Unable to send password reset email. Please try again later." in source,
    "Partner password reset failures should show a generic user-facing error",
)
require(
    "print_r($partner->errors" not in source and "var_dump($partner->errors" not in source,
    "Partner password reset failures should not expose raw model errors to the session flash",
)

print("PASS partner password reset token failures log details without exposing raw errors")
