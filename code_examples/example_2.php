<?php

namespace app\modules\backend\controllers\crm;

use app\models\crm\Category;
use app\models\crm\Performance;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class FestivalCategoryController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        if (empty(Category::findOne(['name' => 'Категории'])))
        {
            $root = new Category();
            $root->name = 'Категории';
            $root->makeRoot()->save();
        }
        return $this->render('index', []);
    }

    public function actionSaveCheckedCategories($performance_id)
    {
        $modelPerformance = Performance::find()->where(['id' => $performance_id])->one();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $payload = json_decode($request->getRawBody(), true);
        $checkedArray = $payload;

        $str = '';
        foreach ($checkedArray as $item)
            $str .= $item . ',';
        $str = mb_substr($str, 0, -1);
        $modelPerformance->categories = $str;
        $modelPerformance->save();

        return ['status' => 200, 'message' => 'OK'];

//      usort($array, function ($a, $b)
//      {
//        return strcmp($a['parent_id'], $b['parent_id']);
//      });

    }

    public static function children($node, $parent_id)
    {
        $children = [];

        $array = [
            'id' => $node->id,
            'text' => $node->name,
            'parent_id' => $parent_id,
            'description' => $node->description,
            'price' => $node->price,
            'nethouse_id' => $node->nethouse_id,
            'children' => $children
        ];
        if ($node->image != null || $node->image != '')
            $array['image'] = '/uploads/category/original/' . $node->image;
        else $array['image'] = null;

        if ($node->children)
        {
            $children = $node->getChildren()->all();
            foreach ($children as $child)
            {
                $item['id'] = $child->id;
                $item['text'] = $child->name;
                $item['parent_id'] = $node->id;
                array_push($array['children'], self::children($child, $node->id));
            }
        }
        return $array;
    }

    public function actionGetTree()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $tree = array();
        $array = array();
        $root = Category::find()->where(['name' => 'Категории'])->one();
        $nodes = $root->children;
        foreach ($nodes as $node) {
            $parent_id = $root->id;
            array_push($array, self::children($node, $parent_id));
        }
        $tree['nodes'] = $array;
        $tree['root'] = $root;
        $data['tree'] = $tree;

        $performance_id = Yii::$app->request->get('performance_id');
        if (!empty($performance_id))
            $modelPerformance = Performance::find()->where(['id' => $performance_id])->one();
        {
            $data['checked'] = array();
            if (!empty($modelPerformance->categories))
            {
                $categories_id = explode(",", $modelPerformance->categories);
                foreach ($categories_id as $item)
                    array_push($data['checked'], (int)$item);
            }
        }
        return $data;
    }

    public function actionCreateNode()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $payload = json_decode($request->getRawBody(), true);

        $root = Category::findOne(['name' => 'Категории']);

        if (empty($payload['text']))
            $message = ['status' => 2, 'text' => 'Указано пустое имя категории'];
        if ($payload['text'] === 'Категории')
            $message = ['status' => 2, 'text' => 'Невозможно создать категорию с этим именем'];
        else {
            if (!empty($payload['parent_id'])) {
                $node = Category::findOne($payload['parent_id']);

                $leaf = new Category();
                $leaf->name = $payload['text'];
                $leaf->appendTo($node)->save();

                $message = ['status' => 200, 'text' => 'OK'];
            } else {
                $node = new Category();

                $name = $payload['text'];
                $node->name = $name;
                if ($root) {
                    $node->appendTo($root)->save();
                } else {
                    $node->appendTo($root)->save();
                }
                $message = ['status' => 200, 'text' => 'OK'];
            }
        }
        return $message;
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @throws \yii\base\InvalidConfigException
     */
    public function actionUpdateNode()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request->getBodyParams();
        if (!empty($request['id'])) {
            $id = $request['id'];
            $category = Category::findOne(['id' => $id]);
            if (!empty($request['text']))
                $category->name = $request['text'];
            if (!empty($request['description']))
                $category->description = $request['description'];
            if (!empty($request['price']))
                $category->price = $request['price'];
            if (!empty($request['nethouse_id']))
                $category->nethouse_id = intval($request['nethouse_id']);
            if (!empty($request['file']))
            {
                $category->image = $request['file'];
////        $category->file->
            }
            $category->save();
            return ['status_code' => 200, 'request' => $request, 'nethouse' => intval($request['nethouse_id'])];
        }
        return ['status_code' => 200, 'request' => $request];
    }

    public function treeToArray($new_tree, $items)
    {
        foreach ($items as $item)
        {
            $node['id'] = $item['id'];
            $node['parent_id'] = $item['parent_id'];
            $node['text'] = $item['text'];
            array_push($new_tree, $node);
            if (!empty($item['children']))
            {
                $new_tree = $this->treeToArray($new_tree, $item['children']);
            }
        }
        return $new_tree;
    }

    public function newPositions($items, $parent_id)
    {

        if ($parent_id === Category::find()->where(['name' => 'Категории'])->one()->id)
            $currentNodes = Category::findOne(['name' => 'Категории'])->children;
        else
            $currentNodes = Category::findOne(['id' => $parent_id])->children;

        // * Устанавливаем новую позицию таргета
        for ($i = 0; $i < count($items); $i++) {
            if ($currentNodes[$i]['id'] != $items[$i]['id'])
            {
                $target = Category::findOne(['id' => $currentNodes[$i]['id']]);
                for ($j = 1; $j < count($items); $j++)
                {
                    if ($target['id'] === $items[$j]['id']){
                        $target->insertAfter(Category::findOne(['id' => $items[$j-1]['id']]))->save();
                        break;
                    }
                }
            }
        }
        // * При наличии потомков запускаем рекурсию
        foreach ($items as $item)
        {
            if (!empty($item['children']))
                $this->newPositions($item['children'], $item['id']);
        }
    }

    public function actionUpdateTree()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $payload = json_decode($request->getRawBody(), true);

        $root = Category::findOne(['name' => 'Категории']);

        $old_tree = $payload['tree'];

        $array = array();
        $array = $this->treeToArray($array, $old_tree);
        usort($array, function ($a, $b)
        {
            return strcmp($a['parent_id'], $b['parent_id']);
        });

        foreach ($array as $item) {
            $node = Category::findOne(['id' => $item['id']]);
            $parent = $node->getParent()->one();
            if ($parent->id != $item['parent_id']) {
                $node->appendTo(Category::findOne(['id' => $item['parent_id']]))->save();
                break;
            }
        }

        $this->newPositions($old_tree, $root->id);

        $data['message'] = "OK";
        $data['status'] = 200;
        return $data;
    }

    public function actionDeleteNode()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $payload = json_decode($request->getRawBody(), true);

        if (!empty($payload['id']))
        {
            $node = Category::findOne(['id' => $payload['id']]);
            $node->deleteWithChildren();
            return ['status' => 200, 'message' => 'Удалено успешно'];
        }
        else
            return ['status' => 400, 'message' => 'Bad request'];
    }

    protected function findModel($id)
    {
        if (($model = Category::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

//  public function access()
//  {
//    return $this->festival_id ? true : false;
//  }

}
