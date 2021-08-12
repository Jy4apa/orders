<?php

use app\models\DropdownList;
use phpnt\yandexMap\YandexMaps;

/* @var $this yii\web\View */

$this->title = 'Система поиска заявок пользователей';
?>
<h1>Система поиска заявок пользователей</h1>
<?php
// Массив меток
//$items = [
//    [
//        'latitude' => 52.906386,
//        'longitude' => 59.954092,
//        'options' => [
//            [
//                'hintContent' => 'Подсказка при наведении на маркет',
//                'balloonContentHeader' => 'Заголовок после нажатия на маркер',
//                'balloonContentBody' => 'Контент после нажатия на маркер',
//                'balloonContentFooter' => 'Футер после нажатия на маркер',
//            ]
//        ]
//    ]
//];
// вывод карты
echo YandexMaps::widget([
    'myPlacemarks' => DropdownList::getDropdownYandexOrders(),
    'mapOptions' => [
        'center' => [47, 39],
        'zoom' => 7,
        'controls' => ['zoomControl', 'fullscreenControl', 'searchControl'],
        'control' => [
            'zoomControl' => [
                'top' => 75,
                'left' => 5
            ],
        ],
    ],
    'disableScroll' => true,
    'windowWidth' => '100%',
    'windowHeight' => '600px',
]);
