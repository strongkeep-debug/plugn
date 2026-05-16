<?php

namespace partner\models;

use common\models\Customer;
use Yii;
use yii\base\Model;
use common\models\Partner;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model {

    public $email;

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist',
                'targetClass' => '\common\models\Partner',
                'targetAttribute' => 'partner_email',
                'filter' => ['partner_status' => Partner::STATUS_ACTIVE],
                'message' => 'There is no partner with this email address.'
            ],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public function sendEmail() {

        $partner = Partner::findOne([
                    'partner_status' => Partner::STATUS_ACTIVE,
                    'partner_email' => $this->email,
        ]);

        if (!$partner) {

            Yii::$app->session->setFlash('error', "No account with provided mail");

            return false;
        }

        //Check if this user sent an email in past few minutes (to limit email spam)
        $currentDatetime = new \DateTime();
        $emailLimitDatetime = null;

        if ($partner->partner_limit_email) {
            $emailLimitDatetime = new \DateTime($partner->partner_limit_email);
            date_add($emailLimitDatetime, date_interval_create_from_date_string('1 minutes'));
        }

        if ($partner->partner_limit_email && $currentDatetime < $emailLimitDatetime) {

            $difference = $currentDatetime->diff($emailLimitDatetime);
            $minuteDifference = (int) $difference->i;
            $secondDifference = (int) $difference->s;

            $errors = Yii::t('api', "Email was sent previously, you may request another one in {numMinutes, number} minutes and {numSeconds, number} seconds", [
                'numMinutes' => $minuteDifference,
                'numSeconds' => $secondDifference,
            ]);

            Yii::$app->session->setFlash('error', $errors);

            return false;
        }

        if (!Partner::isPasswordResetTokenValid($partner->partner_password_reset_token)) {

            $partner->generatePasswordResetToken();

            $partner->setScenario(Partner::SCENARIO_PASSWORD_TOKEN);

            if (!$partner->save()) {
                Yii::error([
                    'message' => 'Failed to save partner password reset token.',
                    'partner_uuid' => $partner->partner_uuid,
                    'errors' => $partner->errors,
                ], __METHOD__);

                Yii::$app->session->setFlash('error', Yii::t('app', 'Unable to send password reset email. Please try again later.'));

                return false;
            }
        }

        Partner::updateAll([
            'partner_limit_email' => new Expression('NOW()')
        ], [
            "partner_uuid" => $partner->partner_uuid
        ]);

        $url = Url::to(['site/reset-password', 'token' => $partner->partner_password_reset_token], true);

        $mailer = Yii::$app->mailer->compose(
                ['html' => 'partner/passwordResetToken-html', 'text' => 'partner/passwordResetToken-text'],
                [
                    'partner' => $partner,
                    "resetLink" => $url
                ]
            )
             ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
             ->setTo($this->email)
             ->setSubject('Reset your password');

        if(\Yii::$app->params['elasticMailIpPool'])
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);

        $mailer->send();

        return true;
    }

}
