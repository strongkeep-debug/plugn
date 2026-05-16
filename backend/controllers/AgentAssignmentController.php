<?php

namespace backend\controllers;

use backend\models\Admin;
use Yii;
use common\models\AgentAssignment;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Agent;
use backend\models\AgentAssignmentSearch;

/**
 * AgentAssignmentController implements the CRUD actions for AgentAssignment model.
 */
class AgentAssignmentController extends Controller {

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
                  [
                      'allow' => Yii::$app->user->identity && Yii::$app->user->identity->admin_role != Admin::ROLE_CUSTOMER_SERVICE_AGENT,
                      'actions' => ['create', 'update', 'delete'],
                      'roles' => ['@'],
                  ],
                  [//allow authenticated users only
                      'allow' => true,
                      'roles' => ['@'],
                  ],
              ],
          ],
      ];
  }


    /**
     * Lists all AgentAssignment models.
     * @return mixed
     */
    public function actionIndex() {

         $searchModel = new AgentAssignmentSearch();
         $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

         return $this->render('index', [
             'searchModel' => $searchModel,
             'dataProvider' => $dataProvider,
         ]);
    }

    /**
     * Displays a single AgentAssignment model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id) {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AgentAssignment model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new AgentAssignment();

        if ($model->load(Yii::$app->request->post())) {

            $agent_model = Agent::findOne($model->agent_id);

            if ($agent_model) {

                $model->assignment_agent_email = $agent_model->agent_email;

                if ($model->validate() && $model->save()) {
                    return $this->redirect(['view', 'id' => $model->assignment_id]);
                } else {
                    Yii::error(json_encode([
                        'message' => 'Failed to save agent assignment.',
                        'agent_id' => $model->agent_id,
                        'errors' => $model->errors,
                    ]), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Unable to save agent assignment. Please review the form and try again.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Selected agent could not be found. Please choose another agent.');
            }
        }

        return $this->render('create', [
             'model' => $model,
        ]);
    }

    /**
     * Updates an existing AgentAssignment model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate() && $model->save()) {
                return $this->redirect(['view', 'id' => $model->assignment_id]);
            } else {
                Yii::error(json_encode([
                    'message' => 'Failed to save agent assignment.',
                    'assignment_id' => $model->assignment_id,
                    'agent_id' => $model->agent_id,
                    'errors' => $model->errors,
                ]), __METHOD__);
                Yii::$app->session->setFlash('error', 'Unable to save agent assignment. Please review the form and try again.');
            }
        }

        return $this->render('update', [
                    'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AgentAssignment model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the AgentAssignment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AgentAssignment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = AgentAssignment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
