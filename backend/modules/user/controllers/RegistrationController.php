<?php

namespace steroids\modules\user\controllers;

use app\auth\forms\EmailConfirm;
use app\auth\forms\RegistrationForm;
use steroids\modules\user\UserModule;
use Yii;
use yii\web\Controller;
use steroids\widgets\ActiveForm;

class RegistrationController extends Controller
{
    public static function coreMenuItems()
    {
        return [
            'user.auth.registration' => [
                'label' => \Yii::t('app', 'Регистрация'),
                'url' => ['/user/registration/index'],
                'urlRule' => 'user/registration',
                'items' => [
                    'email-confirm' => [
                        'label' => \Yii::t('app', 'Подтверждение email'),
                        'url' => ['/auth/registration/email-confirm'],
                        'urlRule' => 'user/registration/email-confirm',
                    ],
                    'success' => [
                        'label' => \Yii::t('app', 'Вы зарегистрировались'),
                        'url' => ['/auth/registration/success'],
                        'urlRule' => 'user/registration/success',
                    ],
                    'agreement' => [
                        'label' => \Yii::t('app', 'Пользовательское соглашение'),
                        'url' => ['/auth/registration/agreement'],
                        'urlRule' => 'user/registration/agreement',
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $model = new RegistrationForm();
        if ($model->load(Yii::$app->request->post()) && $model->register()) {
            return $this->redirect(UserModule::getInstance()->registrationRedirectUrl ?: ['success']);
        }
        if (Yii::$app->request->isAjax) {
            return ActiveForm::renderAjax($model);
        }

        return $this->render('registration', [
            'model' => $model,
        ]);
    }

    public function actionEmailConfirm()
    {
        $model = new EmailConfirm();
        $model->load(array_merge(
            Yii::$app->request->get(),
            Yii::$app->request->post()
        ));

        if ($model->confirm()) {
            \Yii::$app->session->setFlash('success', \Yii::t('app', 'Email успешно подтверджен!'));
            return $this->goHome();
        }
        if (Yii::$app->request->isAjax) {
            return ActiveForm::renderAjax($model);
        }

        return $this->render('email-confirm', [
            'model' => $model,
        ]);
    }

    public function actionSuccess()
    {
        return $this->render('success');
    }

    public function actionAgreement()
    {
        return $this->render('agreement');
    }
}