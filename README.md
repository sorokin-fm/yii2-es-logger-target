By default, Yii2 is configured to write logs into files and if you want to send it contents to kibana, you can use filebeat as it is preferred way to do it. But, let think a little. Why do we need all of this overhead just to send logs to kibana. Why just don't send it directly to ElasticSearch?

Now you can do it directly:

```
compose require "sorokin-fm/yii2-es-logger-target"
```

And add it to component section:

```
    ...
    'components' => [
        ...
        'log' => [
            'targets' => new \yii\helpers\ReplaceArrayValue([
                [
                    'class' => 'common\components\ElasticSearchLogTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'except' => [
                        'yii\db\*',
                        'yii\web\*',
                    ],
                    'logVars' => [],
                    'index' => 'app-' . date('Y-m-d'),
                    'type' => 'app',
                    'body' => [
                        'site' => 'wmcentre.cc',
                    ],
                    'hosts' => ['http://kibana:Yk9K8PpRwqURUCMA@localhost:9200'],
                ],
            ]),
        ],
        ...
    ],
```

Now all of your logs are sending directly into ElasticSearch and we can stop at that point, but ... there's another improvement.

Let's imagine that you have some process, that consists of many stages and components implementing it. For example you are sending email from console command and for the first you need to get it from database and then send it through mail engine. Database fetcher logs some information and email sender too. And you wanted that all of them use some additional information to log. So, how to do it?

Of course, you can use that form of logging:

```
Yii::info([
    'message' => 'Real message',
    'context-information-1' => 'Some additional information',
    'context-information-2' => 'Some additional information',
]);
```

And it will works. But it's a cumbersome a little, right? And, what is more important, it introduce unnecessary links between program modules. We need to pass information into a module and then use it. inside of it. And sometimes there's a situation where we cannot rewrite a module, because at least it's not yours and maybe it also included via composer.


So, what would be better then rewriting modules to improve logging? Let me offer to you a some kind of solution - ScopedLogger.

ScopedLogger it's a tool, that helps to add some kind of additional information at higher level, that will be used automatically at lower once. Let see how it works:

```
function fn1() {

    Yii::info('Message fn1.1');

    fn2();

    Yii::info('Message fn1.2');

}

function fn2() {

    $scope = new SorokinFM\LoggerScopeManager([
        'context-information-1' => 'Some additional information',
    ]);

    Yii::info('Message fn2.1');

    fn3();

    Yii::info('Message fn2.2');

    $scope = null;

}

function fn3() {

    $scope = new SorokinFM\LoggerScopeManager([
        'context-information-2' => 'Some additional information',
    ]);

    Yii::info('Message fn3.1');

    Yii::info('Message fn3.2');

    $scope = null;

}

```

In this example, we will get 6 messages sent to ElasticSearch. And here they are:

```
{
    'message' => 'Message fn1.1'
},
{
    'message' => 'Message fn2.1',
    'context-information-1' => 'Some additional information',
},
{
    'message' => 'Message fn3.1',
    'context-information-1' => 'Some additional information',
    'context-information-2' => 'Some additional information'
},
{
    'message' => 'Message fn3.2',
    'context-information-1' => 'Some additional information',
    'context-information-2' => 'Some additional information'
},
{
    'message' => 'Message fn2.2',
    'context-information-1' => 'Some additional information',
},
{
    'message' => 'Message fn1.2'
}
```

That's cool, right? And only thing you will need to use it it just change logger via Dependency Injection mechanism:

```
    'container' => [
        'singletons' => [
            'yii\log\Logger' => ['class' => 'SorokinFM\ScopedLogger'],
        ],
    ],
```

Happy logging!
