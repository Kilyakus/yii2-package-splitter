<?php
namespace kilyakus\package\splitter\widgets;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use kilyakus\package\splitter\models\SplitterText;
use kilyakus\widget\redactor\Redactor;

class SplitterForm extends Widget
{
    public $form;

    public $model;

    public $attribute;

    public $redactorOptions = [
        // 'usePresets' => false,
        'pluginOptions' => [
            'tabsize' => 2,
            'minHeight' => 150,
            'maxHeight' => 400,
            'focus' => true,
            // 'toolbar' => [
            //     ['style1', ['style', 'clear', 'hr']],
            //     ['insert', ['link', 'picture', 'video', 'table']],
            // ],
        ],
    ];

    public $uploadUrl;

    protected $redactorPresets = [
        'theme' => Redactor::THEME_SIMPLE,
        'fullscreen' => true,
        'codemirror' => true,
        'emoji' => true,
        'pluginOptions' => [
            'tabsize' => 2,
            'minHeight' => 150,
            'maxHeight' => 400,
            'focus' => true,
        ],
    ];

    public function init()
    {
        parent::init();

        if (empty($this->model)) {
            throw new InvalidConfigException('Required `model` param isn\'t set.');
        }

        if (empty($this->attribute)) {
            throw new InvalidConfigException('Required `attribute` param isn\'t set.');
        }

        if(!empty($this->redactorOptions)){

            $redactorOptions = $this->redactorOptions;

            foreach ($this->redactorPresets as $attribute => $option) {
                if($attribute == 'toolbar' && $this->redactorOptions[$attribute]){
                    foreach ($this->redactorOptions[$attribute] as $pluginKey => $pluginAttribute) {
                        foreach ($option as $optionKey => $optionAttribute) {
                            if($pluginAttribute[0] == $optionAttribute[0]){
                                $this->redactorPresets[$attribute][$optionKey] = $this->redactorOptions[$attribute][$pluginKey];
                                unset($this->redactorOptions[$attribute][$pluginKey]);
                            }
                        }
                    }
                }
            }

            $this->redactorOptions = ArrayHelper::merge($this->redactorPresets, $this->redactorOptions);
        }

        $this->redactorOptions['uploadUrl'] = Url::to(['/redactor/upload/images', 'dir' => '/redactor/' . Yii::$app->user->id . '/images']);
    }

    public function run()
    {
        $translations = SplitterText::find()->where(['class' => $this->model::className(), 'item_id' => $this->model->primaryKey])->asArray()->all();
        $translations = ArrayHelper::index($translations, 'lang');

        if(get_class($this->model->splitterText) == get_class(new SplitterText())){
            $model = $this->model->splitterText;
        }else{
            $model = new SplitterText();
        }

        $model->translations = $translations;

        if(empty($model->translations)){

            foreach (Yii::$app->urlManager->languages as $language) {

                $fields = [];

                if(isset($this->model->title)){
                    $fields['title'] = $this->model->title;
                }

                if(isset($this->model->short)){
                    $fields['short'] = $this->model->short;
                }

                if(isset($this->model->text)){
                    $fields['text'] = $this->model->text;
                }

                if(isset($this->model->description)){
                    $fields['description'] = $this->model->description;
                }

                $model->translations[$language] = $fields;
            }
        }

        echo $this->render('translation_form', [
            'form' => $this->form,
            'model' => $model,
            'attribute' => $this->attribute,
            'redactorOptions' => $this->redactorOptions,
        ]);
    }

}