<?php

namespace app\controllers;


//здесь просто вычлененный кусок из всего кода
public function actionSpeakers()
{

    $festivals = Festival::find()
        ->joinWith('performances')
        ->joinWith('sections')
        ->where(['performance.status' => [Performance::STATUS_ORDER_OK, Performance::STATUS_ORDER_CHECKED], 'festival.id' => 3])
        ->orWhere(['section.status' => Section::STATUS_PUBLISH, 'festival.id' => 3])
        ->orderBy(['festival.id' => SORT_DESC])
        ->all();

    $speakers = User::find()
        ->joinWith('performances')
        ->joinWith('sections')
        ->where(['performance.status' => [Performance::STATUS_ORDER_OK, Performance::STATUS_ORDER_CHECKED], 'user.role' => User::ROLE_SPEAKER])
        ->orWhere(['section.status' => Section::STATUS_PUBLISH])
        ->orderBy(['lastname' => SORT_ASC])
        ->all();

    $sospeakers = [];

    foreach ($speakers as $speaker) {
        foreach ($speaker->performances as $performance) {
            if ($performance->status == Performance::STATUS_ORDER_OK || $performance->status == Performance::STATUS_ORDER_CHECKED) {
                if ($speaker['festivals'] == null || $speaker['festivals'] == '')
                    $speaker['festivals'] = $performance['festival_id'];
                elseif (!in_array(strval($performance['festival_id']), explode(',', $speaker['festivals'])))
                    $speaker['festivals'] .= ',' . $performance['festival_id'];
                foreach ($performance->sospeakers as $sospeaker) {
                    if ($sospeaker['festivals'] == null || $sospeaker['festivals'] == '')
                        $sospeaker['festivals'] = $performance['festival_id'];
                    else
                        $sospeaker['festivals'] .= ',' . $performance['festival_id'];
                    $sospeakers[] = $sospeaker;
                }
            }
        }
        foreach ($speaker->sections as $section) {
            if ($section->status == Section::STATUS_PUBLISH) {
                if ($speaker['festivals'] == null || $speaker['festivals'] == '')
                    $speaker['festivals'] = $section['festival_id'];
                else {
                    $speaker_festivals = explode(",", $speaker['festivals']);
                    if (!in_array(strval($section['festival_id']), $speaker_festivals))
                        $speaker['festivals'] .= ',' . $section['festival_id'];
                }
            }
        }
    }
    foreach ($sospeakers as $sospeaker) {
        $keys = array_column($speakers, 'id');
        if (!in_array($sospeaker->id, $keys))
            array_push($speakers, $sospeaker);
        else {
            $index = array_search($sospeaker->id, $keys);
            $speakerFestivals = explode(',', $speakers[$index]['festivals']);
            $sospeakerFestivals = explode(',', $sospeaker['festivals']);
            foreach ($sospeakerFestivals as $item)
                array_push($speakerFestivals, $item);
            $resultFestivals = array_unique($speakerFestivals);
            $speakers[$index]['festivals'] = implode(',', $resultFestivals);
        }
    }
    usort($speakers, function ($a, $b) {
        return $a->lastname > $b->lastname;
    });

// вывод спикеров
    return $this->render('speakers', [
        'speakers' => $speakers,
        'festivals' => $festivals
    ]);
}