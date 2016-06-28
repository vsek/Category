<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Form;

/**
 * Description of CategoryPresenter
 *
 * @author vsek
 */
class CategoryPresenter extends BasePresenterM{
    /** @var \App\Model\Module\Category @inject */
    public $model;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $row = null;
    
    private $tree = array();
    
    public function startup() {
        parent::startup();
        $this->tree[0] = $this->translator->translate('category.noParent');
    }
    
    private function createTreeSelect(\Nette\Database\Table\ActiveRow $page = null, $level = 0){
        if(is_null($page)){
            $pages = $this->model->where('parent_id ?', null);
        }else{
            $pages = $this->model->where('parent_id = ?', $page['id']);
        }
        foreach($pages->order('position') as $pag){
            $name = '';
            for($i = 0; $i < $level; $i++){
                $name .= '-';
            }
            if(is_null($this->row) || $this->row['id'] != $pag['id']){
                $this->tree[$pag['id']] = $name .=  ' ' . $pag['name'];
            }
            $level++;
            $this->createTreeSelect($pag, $level);
            $level--;

        }
    }
    
    /**
     * Zmena poradi
     * @param integer $id ID polozky ktera se posouva
     * @param string $order smer posunuti
     */
    public function actionOrdering($id, $order){
        $this->exist($id);
        if($order == 'down'){
            $down = $this->model->where('position > ?', $this->row['position'])->where('parent_id ?', $this->row['parent_id'])->order('position ASC')->limit(1)->fetch();
            $position = $down->position;
            $down->update(array('position' => $this->row['position']));
            $this->row->update(array('position' => $position));
        }else{
            $up = $this->model->where('position < ?', $this->row['position'])->where('parent_id ?', $this->row['parent_id'])->order('position DESC')->limit(1)->fetch();
            $position = $up->position;
            $up->update(array('position' => $this->row->position));
            $this->row->update(array('position' => $position));
        }
        $this->flashMessage($this->translator->translate('category.orderChanged'));
        $this->redirect('default');
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        if($values['link'] == ''){
            $values['link'] = \Nette\Utils\Strings::webalize($values['name']);
        }else{
            $values['link'] = \Nette\Utils\Strings::webalize($values['link']);
        }
        $category = $this->model->where('link', $values['link'])
                ->where('parent_id', (int)$values['parent_id'] == 0 ? null : (int)$values['parent_id'])
                ->where('NOT id', $this->row['id'])
                ->fetch();
        if($category){
            $form['link']->addError($this->translator->translate('category.linkExist'));
        }else{
            $data = array(
                'name' => $values->name,
                'link' => $values['link'],
                'parent_id' => (int)$values->parent_id == 0 ? null : (int)$values->parent_id,
            );
            $this->row->update($data);

            $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
            $this->redirect('edit', $this->row->id);
        }
    }
    
    private function exist($id){
        $this->row = $this->model->get($id);
        if(!$this->row){
            $this->flashMessage($this->translator->translate('admin.text.notitemNotExist'), 'error');
            $this->redirect('default');
        }
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);
        
        $this->createTreeSelect();
        
        $form->addText('name', $this->translator->translate('category.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('link', $this->translator->translate('category.link'));
        $form->addSelect('parent_id', $this->translator->translate('category.parentCategory'), $this->tree);
        
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = [$this, 'submitFormEdit'];
        
        $form->setDefaults(array(
            'name' => $this->row->name,
            'link' => $this->row->link,
            'parent_id' => $this->row->parent_id,
        ));
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->row->delete();
        $this->flashMessage($this->translator->translate('admin.text.itemDeleted'));
        $this->redirect('default');
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();

        if($values->link == ''){
            $link = \Nette\Utils\Strings::webalize($values->name);
        }else{
            $link = \Nette\Utils\Strings::webalize($values->link);
        }
        
        $category = $this->model->where('link', $link)->where('parent_id', (int)$values['parent_id'] == 0 ? null : (int)$values['parent_id'])->fetch();
        if($category){
            $form['link']->addError($this->translator->translate('category.linkExist'));
        }else{
            $this->model->insert(array(
                'name' => $values->name,
                'link' => $link,
                'parent_id' => (int)$values->parent_id == 0 ? null : (int)$values->parent_id,
            ));

            $this->flashMessage($this->translator->translate('admin.text.inserted'));
            $this->redirect('default');
        }
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        $this->createTreeSelect();
        
        $form->addText('name', $this->translator->translate('category.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('link', $this->translator->translate('category.link'));
        $form->addSelect('parent_id', $this->translator->translate('category.parentCategory'), $this->tree);
        
        $form->addSubmit('send', $this->translator->translate('admin.form.create'));
        
        $form->onSuccess[] = [$this, 'submitFormNew'];
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\GridTree('category');

        $grid->setModel($this->model->where('parent_id ?', null));
        $grid->addColumn(new \App\Grid\Column\Column('name', $this->translator->translate('category.name')));
        $grid->addColumn(new \App\Grid\Column\Column('link', $this->translator->translate('category.link')));
        $grid->addColumn(new \App\Grid\Column\Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('position');
        
        $grid->setOrdering('ordering');
        
        return $grid;
    }
}
