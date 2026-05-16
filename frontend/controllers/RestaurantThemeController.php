<?php

namespace frontend\controllers;

use Yii;
use common\models\RestaurantTheme;
use common\models\AgentAssignment;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RestaurantThemeController implements the CRUD actions for RestaurantTheme model.
 */
class RestaurantThemeController extends Controller {

    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [//allow authenticated users only
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays a single RestaurantTheme model.
     * @param string $storeUuid
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionIndex($storeUuid) {

      $model = $this->findModel($storeUuid);

      if ($model->load(Yii::$app->request->post()) && $model->save()) {
          return $this->redirect(['index', 'storeUuid' => $model->restaurant_uuid]);
      }

      return $this->render('index', [
                  'model' => $model,
      ]);
    }


    /**
     * Finds the RestaurantTheme model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return RestaurantTheme the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($storeUuid) {
        $managedAccount = Yii::$app->accountManager->getManagedAccount($storeUuid);

        if ($managedAccount) {

            if (Yii::$app->user->identity->isOwner($storeUuid)) {
                if (($model = RestaurantTheme::findOne($managedAccount->restaurant_uuid)) !== null) {
                    return $model;
                }

                throw new NotFoundHttpException('The requested page does not exist.');
            } else {
                throw new \yii\web\BadRequestHttpException('Sorry, you are not allowed to access this page.');
            }
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
