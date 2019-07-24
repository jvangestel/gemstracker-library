<?php


class Gems_Default_SurveyCodeBookAction extends \Gems_Controller_ModelSnippetActionAbstract
{

    protected $surveyId;

    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getSurveyCodeBookModel($this->surveyId);

        //$test = $model->load();

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->_('Codebook');
    }

    public function getTopicTitle()
    {
        return $this->_('Codebook');
    }

    public function exportAction()
    {
        $this->surveyId = $this->request->getParam(\MUtil_Model::REQUEST_ID);
        if ($this->surveyId == false) {
            throw new \Exception('No Survey ID set');
        }

        parent::exportAction();
    }

    public function exportMultipleAction()
    {

    }
}