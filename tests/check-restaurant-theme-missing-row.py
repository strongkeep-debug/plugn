from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
controller = ROOT / "frontend" / "controllers" / "RestaurantThemeController.php"
source = controller.read_text(encoding="utf-8")
compact = "".join(source.split())


def require(condition, message):
    if not condition:
        raise SystemExit(message)


require(
    "$managedAccount = Yii::$app->accountManager->getManagedAccount($storeUuid);" in source,
    "Restaurant theme lookup should store the managed account before using its restaurant uuid",
)
require(
    "RestaurantTheme::findOne($managedAccount->restaurant_uuid)" in source,
    "Restaurant theme lookup should use the already-validated managed account restaurant uuid",
)
require(
    "getManagedAccount($storeUuid)->restaurant_uuid" not in source,
    "Restaurant theme lookup should not dereference a repeated managed-account lookup",
)
require(
    (
        "if(($model=RestaurantTheme::findOne($managedAccount->restaurant_uuid))!==null)"
        "{return$model;}thrownewNotFoundHttpException('Therequestedpagedoesnotexist.');"
    )
    in compact,
    "Missing restaurant theme rows should throw NotFoundHttpException instead of falling through",
)

print("PASS restaurant theme lookup fails closed when the theme row is missing")
