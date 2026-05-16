from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
controller = ROOT / "crm" / "modules" / "v1" / "controllers" / "TicketController.php"
source = controller.read_text(encoding="utf-8")


def require(condition, message):
    if not condition:
        raise SystemExit(message)


require(
    "use common\\models\\AgentAssignment;" in source,
    "TicketController should import AgentAssignment constants",
)
require(
    "->one()->agent_id" not in source,
    "TicketController should not dereference a nullable owner assignment query",
)
require(
    "$restaurant = $model->restaurant;" in source and "Restaurant not found" in source,
    "TicketController should return a structured error when the restaurant is missing",
)
require(
    "CRM ticket create failed: restaurant not found" in source
    and "CRM ticket create failed: owner assignment missing" in source,
    "TicketController should log CRM ticket create setup failures",
)
require(
    "$ownerAssignment" in source
    and "AgentAssignment::AGENT_ROLE_OWNER" in source
    and "Restaurant owner agent assignment is missing" in source,
    "TicketController should return a structured error when no owner assignment exists",
)
require(
    'getBodyParam("attachments", [])' in source
    and "is_array($attachments)" in source
    and "Attachments must be an array" in source,
    "TicketController should validate the optional attachments payload before ArrayHelper::getColumn",
)

print("PASS CRM ticket create validates restaurant, owner assignment, and attachments")
