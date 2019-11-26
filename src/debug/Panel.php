<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\debug;

use Yii;
use yii\helpers\VarDumper;
use yii\queue\JobInterface;
use yii\queue\PushEvent;
use yii\queue\Queue;

/**
 * Debug Panel.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Panel extends \yii\debug\Panel
{
    private $_jobs = [];


    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Queue';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        PushEvent::on(Queue::class, Queue::EVENT_AFTER_PUSH, function (PushEvent $event) {
            $this->_jobs[] = $this->getPushData($event);
        });
    }

    /**
     * @param PushEvent $event
     * @return array
     */
    protected function getPushData(PushEvent $event)
    {
        $data = [];
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $event->sender) {
                $data['sender'] = $id;
                break;
            }
        }
        $data['id'] = $event->id;
        $data['ttr'] = $event->ttr;
        $data['delay'] = $event->delay;
        $data['priority'] = $event->priority;
        if ($event->job instanceof JobInterface) {
            $data['class'] = get_class($event->job);
            $data['properties'] = [];
            foreach (get_object_vars($event->job) as $property => $value) {
                $data['properties'][$property] = VarDumper::dumpAsString($value);
            }
        } else {
            $data['data'] = VarDumper::dumpAsString($event->job);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return ['jobs' => $this->_jobs];
    }
}
