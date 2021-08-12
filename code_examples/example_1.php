<?php

namespace app\controllers;

use app\components\Controller;
use app\components\traits\SpeakerModelTrait;
use app\models\crm\Category;
use app\models\crm\Festival;
use app\models\crm\Payment;
use app\models\crm\Performance;
use app\models\Presentation;
use app\models\Speaker;
use app\models\User;
use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class LibraryController extends Controller
{
    use SpeakerModelTrait;

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionCategories()
    {
        return $this->render('categories');
    }

    public function actionGetCategories()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $query = [];
        $user_id = null;
        $categories = Category::find()->where(['name' => 'Категории'])->one()->children;
        $authorized = null;
        foreach ($categories as $category) {
            if ($category->image != null || $category->image != '')
                $category->image = '/uploads/category/original/' . $category->image;
            else $category->image = null;
            $query[] = $category;
        }

        if (Yii::$app->user->identity) {
            $favorites = User::findOne(['id' => Yii::$app->user->getId()])->favorite_categories;
            if (strpos($favorites, ','))
                $result = explode(',', $favorites);
            else $result[] = $favorites;

            foreach ($query as $item) {
                if (in_array($item->id, $result))
                    $item['is_favorite'] = true;
                else $item['is_favorite'] = false;
            }
            $authorized = true;
        } else {
            foreach ($query as $item) {
                $item['is_favorite'] = false;
            }
            $authorized = false;
        }

        return ['categories' => $query, 'authorized' => $authorized];

    }

    public function actionGetPerformance()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request->get();
        $user_id = Yii::$app->user->getId();
        $data = [];
        $data['access'] = false;

        if (!empty($user_id)) {
            $payments = Payment::find()->where(['user_id' => $user_id])
                ->andWhere(['status' => true])
                ->all();
            $performancesArray = [];
            foreach ($payments as $payment) {
                if ($payment->type_of_payment === 'Покупка' || ($payment->type_of_payment === 'Подписка' && strtotime($payment->sub_end_at) > time())) {
                    if ($payment->type_of_product === 'Фестиваль') {
                        $festival_id = $payment->product_of_type_id;
                        $performances = Performance::find()->where(['festival_id' => $festival_id])->all();
                        foreach ($performances as $performance)
                            $performancesArray[] = $performance->id;
                    }
                    if ($payment->type_of_product === 'Категория') {
                        $category_id = $payment->product_of_type_id;
                        $performances = Performance::find();

                        $parent = Category::findOne(['id' => $category_id]);
                        $categoriesArray = [];
                        $categoriesArray = self::children($parent, $categoriesArray);
                        foreach ($categoriesArray as $item) {
                            foreach ($performances->all() as $performance) {
                                $categories = explode(',', $performance->categories);
                                if (in_array($item, $categories)) {
                                    if (!in_array($performance->id, $performancesArray)) {
                                        $performancesArray[] = $performance->id;
                                    }
                                }
                            }
                        }
                    }
                    if ($payment->type_of_product === 'Выступление') {
                        $performance = Performance::findOne(['id' => $payment->product_of_type_id]);
                        if (!in_array($performance->id, $performancesArray))
                            $performancesArray[] = $performance->id;
                    }
                }
            }
        }
        if (!empty($request['performanceId'])) {
            $performance_id = (int)json_decode($request['performanceId'], true);
            $performance = Performance::findOne(['id' => $performance_id]);
            $presentations = Presentation::find()->where(['performance_id' => $performance_id])->all();

            if ($performance != null) {
                $data['status'] = 200;
                $data['message'] = 'OK';
                $user = User::findOne(['id' => $user_id]);
                if (!empty($user)) {
                    if (strpos($user->favorite_performances, ','))
                        $favorites = explode(',', $user->favorite_performances);
                    else $favorites[] = $user->favorite_performances;

                    if (in_array($performance->id, $favorites))
                        $performance['is_favorite'] = true;
                    else $performance['is_favorite'] = false;

                    if ($user->role === 'admin' || in_array($performance->id, $performancesArray)) {
                        $data['access'] = true;
                    } elseif ($performance->price > 0 && !in_array($performance->id, $performancesArray)) {
                        $data['access'] = false;
                    }
                }
                if ($performance->price == 0 || $performance->price === null)
                    $data['access'] = true;

                if ($data['access'] === false) {
                    $performance->video = null;
                    $presentations = null;
                }
                $data['performance'] = $performance;
                $data['presentation'] = $presentations;
            } else {
                $data['status'] = 400;
                $data['message'] = 'Выступление не найдено';
            }
        }
        if (Yii::$app->user->identity)
            $data['authorized'] = true;
        else $data['authorized'] = false;

        return $data;
    }

    public function actionPerformance($id)
    {
        $performance = $this->findPerformance($id);

        return $this->render('performance', [
            'model' => $performance,
        ]);
    }

    public function actionCategory($id)
    {
        $model = $this->findModel($id);
        if (!$model) throw new NotFoundHttpException('Страница не найдена в списке доступных страниц.');

        return $this->render('category', [
            'model' => $model,
        ]);
    }

    public function actionFestival($id)
    {
        $model = Festival::findOne(['id' => $id]);
        if (!$model) throw new NotFoundHttpException('Страница не найдена в списке доступных страниц.');

        return $this->render('festival', [
            'model' => $model,
        ]);
    }

    public static function children($node, $categories)
    {
        array_push($categories, $node->id);
        if ($node->children) {
            $children = $node->children;
            foreach ($children as $child)
                $categories = self::children($child, $categories);
        }
        return $categories;
    }

    public function actionGetPerformances()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request->get();
        $current_festival = Festival::find()->where(['id' => SORT_DESC])->one();

        $festival_id = null; // * Фильтрация по фестивалю
        $category_id = null; // * Фильтрация по категории
        $speaker_id = null; // * Фильтрация по спикеру
        $group = null; // * Фильтрация по платным/бесплатным
        $content = null; // * Содержимое выступления (презентации/видео)
        $search = null; // * Поиск
        $query = []; // * Результат выборки
        $performances = Performance::find()->joinWith('festival')->where(['festival.status' => 3]);

        // * Фильтрация выступлений

        if (!empty($request['filter'])) {
            $filter = json_decode($request['filter'], true);

            if (!empty($filter['content']))
                $content = $filter['content'];
            if (!empty($filter['group']))
                $group = $filter['group'];
            if (!empty($filter['festival_id']))
                $festival_id = (int)$filter['festival_id'];
            if (!empty($filter['category_id']))
                $category_id = (int)$filter['category_id'];
            if (!empty($filter['speaker_id']))
                $speaker_id = (int)$filter['speaker_id'];
        }

        if (empty($filter))
            $query = $performances->all();

        if (!empty($request['search'])) // * Поиск
        {
            $search = json_decode($request['search'], true);
            if ($search['type'] != null && $search['type'] != '' && $search['name'] != null && $search['name'] != '') {
                $type = $search['type'];
                $name = $search['name'];
                if ($type == 'performance')
                    $performances->andWhere(['title' => $name]);
                if ($type == 'speaker') {
                    $search_name = explode(' ', $name);
                    $speaker = User::find()
                        ->where(['lastname' => $search_name[0]])
                        ->andWhere(['firstname' => $search_name[1]])
                        ->one();
                    $performances->andWhere(['speaker_id' => $speaker->id]);
                }
            }
        }
        if (!empty($filter)) {
            if (!empty($content)) {
                if (isset($content['video'])) {
                    if ($content['video'] == true)
                        $performances->andWhere(['!=', 'video', '']);
                }
                if (isset($content['presentation'])) {
                    if ($content['presentation'] == true) {
                        $performances->leftJoin('presentation', 'presentation.performance_id = performance.id');
                        $performances->andWhere(['not', ['presentation.performance_id' => null]]);
                    }
                }
            }
            if (!empty($group)) {
                if ($group == 'paid') // * Платные
                    $performances
                        ->andWhere(['!=', 'performance.price', 0]);
                if ($group == 'free') // * Бесплатные
                    $performances
                        ->andWhere(['performance.price' => 0]);
                if ($group == 'all' && $category_id == null && $speaker_id == null)
                    $query = $performances->all();
            }
            if ($festival_id != null) {
                $performances->andWhere(['performance.festival_id' => $festival_id]);
                $query = $performances->all();
            }
            if ($category_id != null) {
                $parent = Category::findOne(['id' => $category_id]);
                $categoriesArray = [];
                $categoriesArray = self::children($parent, $categoriesArray);
                $performancesArray = [];
                foreach ($categoriesArray as $item) {
                    foreach ($performances->all() as $performance) {
                        $categories = explode(',', $performance->categories);
                        if (in_array($item, $categories)) {
                            if (!in_array($performance->id, $performancesArray)) {
                                $performancesArray[] = $performance->id;
                                $query[] = $performance;
                            }
                        }
                    }
                }
            }
            if ($speaker_id != null) {
                $performances->andWhere(['speaker_id' => $speaker_id]);
                $query = $performances->all();
                $data['current_performances'] = Performance::find()->where(['festival_id' => $current_festival->id, 'speaker_id' => $speaker_id])->all();
            }
            if ($speaker_id == null && $category_id == null)
                $query = $performances->all();
        }
        // * Сортировка

        $direction = 'desc_date';
        if (!empty($request['order'])) {
            $order = json_decode($request['order'], true);
            if (!empty($order['direction']))
                $direction = $order['direction'];
        }
        if ($direction === 'desc_date') {
            usort($query, function ($a, $b) {
                return $a->id < $b->id;
            });
        }
        if ($direction === 'asc_date') {
            usort($query, function ($a, $b) {
                return $a->id > $b->id;
            });
        }
        if ($direction === 'desc_price') {
            usort($query, function ($a, $b) {
                return $a->price < $b->price;
            });
        }
        if ($direction === 'asc_price') {
            usort($query, function ($a, $b) {
                return $a->price > $b->price;
            });
        }

        // * Пагинация с делением по количеству выступлений

        $offset = 0;
        $limit = 10;
        if (!empty($request['pagination'])) {
            $pagination = json_decode($request['pagination'], true);
            $limit = (int)$pagination['limit'];
            $offset = (int)$pagination['offset'];
        }
        $data['total'] = count($query);
        $query = array_slice($query, $offset, $limit);

        // * Избранные выступления

        $user_id = Yii::$app->user->getId();

        $favorites = [];
        if (!empty($user_id)) {
            $user = User::findOne(['id' => $user_id]);
            if (strpos($user->favorite_performances, ',')) {
                $array = explode(',', $user->favorite_performances);
                foreach ($array as $item)
                    $favorites[] = (int)$item;
            } else
                $favorites[] = (int)$user->favorite_performances;
        }

        foreach ($query as $item) {
            if (in_array($item['id'], $favorites) == true) // * Является ли избранным
                $item['is_favorite'] = true;
            else $item['is_favorite'] = false;

            if ($item->presentation)
                $item['has_presentation'] = true;
            else $item['has_presentation'] = false;

            if ($item->video != null || $item->video != '')
                $item['video'] = true;
            else $item->video = false;
        }

        // * Авторизованность пользователя
        if (Yii::$app->user->isGuest)
            $data['authorized'] = false;
        else $data['authorized'] = true;

        // * Категории
        $categories = Category::find()->where(['name' => "Категории"])->one()->children;
        foreach ($categories as $category)
        {
            if ($category->image != null || $category->image != '')
                $category->image = '/uploads/category/original/' . $category->image;
            else $category->image = null;
        }
        $data['categories'] = $categories;
        $data['status'] = 200;
        $data['message'] = 'OK';
        $data['search'] = $search;

        // * Фестивали
        $data['festivals'] = Festival::find()->all();

        $data['performances'] = $query;
        return $data;
    }

    /**
     * @param $id
     * @return Category|array|ActiveRecord
     */
    private function findModel($id)
    {
        return Category::findOne(['id' => $id]);
    }

    /**
     * @param $id
     * @return Performance|array|ActiveRecord
     */
    private function findPerformance($id)
    {
        return Performance::findOne(['id' => $id]);
    }

    // * Получение/добавление избранных выступлений/категорий

    public function actionPostFavoriteCategory()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = json_decode(Yii::$app->request->getRawBody(), true);
        $user_id = Yii::$app->user->getId();
        $category_id = null;
        $status = null;
        $data = [];
        if (!empty($request['category_id']))
            $category_id = $request['category_id'];
        else $data['category_id'] = 'Не удалось получить category_id';
        if ($request['status'] == true)
            $status = true;
        else $status = false;

        if (!empty($user_id) && $category_id != null) {
            $user = User::findOne(['id' => $user_id]);
            if ($status == true) {
                if ($user->favorite_categories == null || $user->favorite_categories == '') {
                    $user->favorite_categories .= $category_id;
                    $user->save();
                } else {
                    $user->favorite_categories .= ',' . $category_id;
                    $user->save();
                }
            } elseif ($status == false) {
                if (strpos($user->favorite_categories, ',') == true) {
                    $favorites = explode(',', $user->favorite_categories);
                    $user->favorite_categories = '';
                    for ($i = 0; $i < count($favorites); $i++) {
                        if ((int)$favorites[$i] != $category_id)
                            $user->favorite_categories .= $favorites[$i] . ',';
                    }
                    $user->favorite_categories = mb_substr($user->favorite_categories, 0, -1);
                    $user->save();
                } elseif (strpos($user->favorite_categories, ',') == false) {
                    $user->favorite_categories = '';
                    $user->save();
                }
            }
            $data['status_code'] = 200;
            $data['status'] = $status;
        }
//    else $data['user'] = 'Не удалось получить пользователя';

        return $data;
    }

    public function actionGetFavoriteCategories()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $user_id = Yii::$app->user->getId();
        $data = [];

        if (!empty($user_id)) {
            $user = User::findOne(['id' => $user_id]);
            if (strpos($user->favorite_categories, ',') === true)
                $categories = explode(',', $user->favorite_categories);
            elseif (strpos($user->favorite_categories, ',') === false)
                $categories = $user->favorite_categories;
            $data['favorite_categories'] = $categories;
        }
        return $data;
    }

    public function actionPostFavoritePerformance()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = json_decode(Yii::$app->request->getRawBody(), true);
        $user_id = Yii::$app->user->getId();
        $performance_id = null;
        $status = null;
        $data = [];

        if (!empty($request['performance_id']))
            $performance_id = $request['performance_id'];
        else $data['performance_id'] = 'Не удалось получить performance_id';
        if ($request['status'] == true)
            $status = true;
        else $status = false;

        if (!empty($user_id) && $performance_id != null) {
            $user = User::findOne(['id' => $user_id]);
            if ($status == true) {
                if ($user->favorite_performances == null || $user->favorite_performances == '') {
                    $user->favorite_performances .= $performance_id;
                    $user->save();
                } else {
                    $user->favorite_performances .= ',' . $performance_id;
                    $user->save();
                }
            } elseif ($status == false) {
                if (strpos($user->favorite_performances, ',') == true) {
                    $favorites = explode(',', $user->favorite_performances);
                    $user->favorite_performances = '';
                    for ($i = 0; $i < count($favorites); $i++) {
                        if ((int)$favorites[$i] != $performance_id)
                            $user->favorite_performances .= $favorites[$i] . ',';
                    }
                    $user->favorite_performances = mb_substr($user->favorite_performances, 0, -1);
                    $user->save();
                } elseif (strpos($user->favorite_performances, ',') == false) {
                    $user->favorite_performances = '';
                    $user->save();
                }
            }
            $data['status_code'] = 200;
            $data['status'] = $status;
        }

        return $data;
    }

    public function actionGetFavoritePerformances()
    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $user_id = Yii::$app->user->getId();
        $data = [];

        if (!empty($user_id)) {
            $user = User::findOne(['id' => $user_id]);
            if (strpos($user->favorite_performances, ',') == true)
                $performances = explode(',', $user->favorite_performances);
            elseif (strpos($user->favorite_performances, ',') == false)
                $performances = $user->favorite_performances;
            $data['favorite_categories'] = $performances;
        }
        return $data;
    }

    public function actionGetCategoryData()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->get();

        if (!empty($request['category_id'])) {
            $category = $this->findModel($request['category_id']);
            if (Yii::$app->user->getId()) {
                $user = User::findOne(['id' => Yii::$app->user->getId()]);
                if (strpos($user->favorite_categories, ',') == true) {
                    $favorites = explode(',', $user->favorite_categories);
                    if (in_array($category['id'], $favorites))
                        $category['is_favorite'] = true;
                    else $category['is_favorite'] = false;
                } elseif (strpos($user->favorite_categories, ',') == false && $user->favorite_categories != '' && $user->favorite_categories != null) {
                    if ($user->favorite_categories == $category['id'])
                        $category['is_favorite'] = true;
                } elseif ($user->favorite_categories == null || $user->favorite_categories == '')
                    $category['is_favorite'] = false;

            }
            if ($category['image'] != null || $category['image'] != '')
                $category['image'] = '/uploads/category/original/' . $category->image;
            else $category['image'] = null;
            $performances = Performance::find();
            $parent = Category::findOne(['id' => $category->id]);
            $categoriesArray = [];
            $categoriesArray = self::children($parent, $categoriesArray);
            $performancesArray = [];
            foreach ($categoriesArray as $item) {
                foreach ($performances->all() as $performance) {
                    $categories = explode(',', $performance->categories);
                    if (in_array($item, $categories)) {
                        if (!in_array($performance->id, $performancesArray)) {
                            $performancesArray[] = $performance->id;
                        }
                    }
                }
            }
            $category['total_count_performances'] = count($performancesArray);
            return $category;
        } else return ['message' => 'Невозможно получить категорию, так как не указан category_id'];
    }

    public function actionGetFestivalData()
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->get();

        if (!empty($request['festival_id'])) {
            $festival = Festival::findOne(['id' => $request['festival_id']]);
            if ($festival['image'] != null || $festival['image'] != '')
                $festival['image'] = '/uploads/category/original/' . $festival->image;
            else $festival['image'] = null;
            $festival['total_count_performances'] = count(Performance::find()->where(['festival_id' => $festival->id])->all());

//      if (!Yii::$app->user->isGuest)
//      {
//        $user = User::findOne(['id' => Yii::$app->user->getId()]);
//        $payments = Payment::find()
//          ->where(['user_email' => $user->email])
//          ->andWhere([''])
//      }
            return $festival;
        } else return ['message' => 'Невозможно получить фестиваль, так как не указан festival_id'];
    }

    public function actionSearchField()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request->getQueryParams();
        if (!empty($request['text'])) {
            $text = $request['text'];

            if ($text == '' || $text == null)
                return [];
            else {
                $result = [];
                $performances = Performance::find()->where(['like', 'title', $text])->limit(5)->all();
                $speakers = [];
                if (strpos($text, ' ')) {
                    $array = explode(' ', $text);
                    $speakersLastname = User::find()->where(['role' => 'speaker'])
                        ->andWhere(['like', 'lastname', $array[0]])
                        ->andWhere(['like', 'firstname', $array[1]])
                        ->limit(5)
                        ->all();
                    $speakersFirstname = User::find()->where(['role' => 'speaker'])
                        ->andWhere(['like', 'firstname', $array[0]])
                        ->andWhere(['like', 'lastname', $array[1]])
                        ->limit(5)
                        ->all();
                    foreach ($speakersLastname as $item)
                        $speakers[] = $item;
                    foreach ($speakersFirstname as $item)
                        $speakers[] = $item;
//          $result[] = $array;
                } else {
                    $users = User::find()
                        ->where(['like', 'lastname', $text])
                        ->orWhere(['like', 'firstname', $text]);
                    $speakers = $users
                        ->andWhere(['role' => 'speaker'])
                        ->limit(5)
                        ->all();
                }
                $categories = Category::find()->where(['like', 'name', $text])->limit(5)->all();

                foreach ($performances as $performance)
                    $result[] = [
                        'name' => $performance->title,
                        'type' => 'performance',
                        'details_url' => '/library/performance/' . $performance->id,
                    ];
                foreach ($speakers as $speaker) {
                    $result[] = [
                        'name' => $speaker->lastname . ' ' . ' ' . $speaker->firstname,
                        'type' => 'speaker',
                        'details_url' => '/speakers/' . $speaker->lastname . '_' . $speaker->firstname,
                    ];
                }
                foreach ($categories as $category)
                    $result[] = [
                        'name' => $category->name,
                        'type' => 'category',
                        'details_url' => '/library/category/' . $category->id,
                    ];
            }
        } else $result = 'Не удалось получить содержимое поиска';

        return $result;
    }

}
