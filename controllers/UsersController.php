<?php

namespace app\controllers;

use app\models\Category;
use app\models\Task;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class UsersController extends Controller
{
    /**
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string {
        $user = User::find()
            ->with(['city', 'categories'])
            ->where(['id' => $id, 'role' => User::ROLE_EXECUTOR])
            ->one();

        if (!$user || $user->role !== User::ROLE_EXECUTOR) {
            throw new NotFoundHttpException("Исполнитель не найден");
        }

        $user->executor_rating = $user->calculateExecutorRating();
        $reviewsDataProvider = new ActiveDataProvider([
            'query' => $user->getExecutorReviews()->with('task.customer'),
            'pagination' => [
                'pageSize' => 5,
            ],
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]]
        ]);
        $completedTasks = Task::find()
            ->where(['executor_id' => $id, 'status' => Task::STATUS_COMPLETED])
        ->count();

        $failedTasks = Task::find()
            ->where(['executor_id' => $id, 'status' => Task::STATUS_FAILED])
            ->count();

        return $this->render('view-user/view', [
            'user' => $user,
            'completedTasks' => $completedTasks,
            'failedTasks' => $failedTasks,
            'reviewsDataProvider' => $reviewsDataProvider,
        ]);
    }
}