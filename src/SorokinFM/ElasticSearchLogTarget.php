<?php

namespace SorokinFM;

use yii\web\Request;
use yii\Log\Logger;
use yii\log\Target;

/**
 * Class ElasticSearchLogTarget
 *
 * Replacement for a traditional file target
 *
 * @package common\components
 */
class ElasticSearchLogTarget extends Target
{
    public $hosts = [];

    public $index = '';

    public $type = '';

    public $body = '-';

    public function export() {
        $client = \Elasticsearch\ClientBuilder::create()
            ->setHosts($this->hosts)
            ->build();

        $params = [
            'index' => $this->index,
            'type' => $this->type,
        ];

        $commonData = [];
        $request = \Yii::$app->getRequest();
        if( $request instanceof Request ) {
            $commonData['ip'] = $request->getUserIP();
        }

        /* @var $user \yii\web\User */
        $user = \Yii::$app->has('user', true) ? \Yii::$app->get('user') : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $commonData['userID'] = $identity->getId();
        }

        /* @var $session \yii\web\Session */
        $session = \Yii::$app->has('session', true) ? \Yii::$app->get('session') : null;
        if( $session && $session->getIsActive() ) {
            $commonData['sessionID'] = $session->getId();
        }

        $commonData['app'] = str_replace("app-", "", \Yii::$app->id);

        foreach( $this->messages as $message ) {
            $body = [];

            list($text, $level, $category, $timestamp) = $message;

            $levelName = Logger::getLevelName($level);

            if( is_array($text) ) {
                $body = array_merge($body,$text);
            } else {
                $body['message'] = $text;
            }

            $body = array_merge($body,$this->body);

            $body['level'] = $levelName;
            $body['category'] = $category;

            $seconds = floor($timestamp);
            $ms = floor(($timestamp-$seconds)*10000);
            $ms = str_pad($ms,4, "0",STR_PAD_LEFT);
            $body['timestamp'] = date('Y-m-d H:i:s', $seconds) . " " . $ms;


            if( isset($message[4]) && $message[4] ) {
                $body['traces'] = $message[4];
            }

            $params['body'] = array_merge($commonData, $body);
            //echo "log: " . json_encode($params) . "\n";
            //echo "hosts: " . json_encode($this->hosts) . "\n";
            try {
                $response = $client->index($params);
            } catch(\Exception $e ) {
                $fileName = \Yii::getAlias('@runtime') . '/logger-errors.log';
                file_put_contents($fileName, $e->getMessage(), FILE_APPEND);
            }
            //echo "echo: " . json_encode($response) . "\n";
        }

    }
}
