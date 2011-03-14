<?php
/**
 * Controller for all computer-related actions.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class ComputerController extends Zend_Controller_Action
{

    public function preDispatch()
    {
        // Fetch computer with given ID for actions referring to a particular computer
        switch ($this->_getParam('action')) {
            case 'index':
            case 'search':
                return; // no specific computer for these actions
        }

        $computer = Model_Computer::fetchById($this->_getParam('id'));
        if ($computer) {
            $this->computer = $computer;
            $this->view->computer = $computer;
            Zend_Registry::set('subNavigation', 'Inventory');
        } else {
            $this->_redirect('computer');
        }
    }

    public function indexAction()
    {
        $this->_helper->ordering('InventoryDate', 'desc');

        $filter = $this->_getParam('filter');
        $search = $this->_getParam('search');
        $exact = $this->_getParam('exact');
        $invert = $this->_getParam('invert');

        if (!$filter) {
            $index = 1;
            while ($this->_getParam('filter' . $index)) {
                $filter[] = $this->_getParam('filter' . $index);
                $search[] = $this->_getParam('search' . $index);
                $exact[] = $this->_getParam('exact' . $index);
                $invert[] = $this->_getParam('invert' . $index);
                $index++;
            }
        }

        $columns = explode(
            ',',
            $this->_getParam(
                'columns',
                'Name,UserName,OsName,Type,CpuClock,PhysicalMemory,InventoryDate'
            )
        );

        $this->view->columns = $columns;

        $this->view->computers = Model_Computer::createStatementStatic(
            $columns,
            $this->view->order,
            $this->view->direction,
            $filter,
            $search,
            $exact,
            $invert
        );

        $jumpto = $this->_getParam('jumpto');
        if (!method_exists($this, $jumpto . 'Action')) {
            $jumpto = 'general'; // Default for missing or invalid argument
        }
        $this->view->jumpto = $jumpto;

        $this->view->filter = $filter;
        $this->view->search = $search;
        $this->view->exact = $exact;
        $this->view->invert = $invert;
        if ($this->_getParam('customFilter')) {
            $this->view->filterUriPart = $this->getFilterUriPart();
        }
    }

    public function generalAction()
    {
    }

    public function windowsAction()
    {
    }

    public function networkAction()
    {
    }

    public function storageAction()
    {
    }

    public function displayAction()
    {
    }

    public function biosAction()
    {
    }

    public function systemAction()
    {
    }

    public function printersAction()
    {
    }

    public function softwareAction()
    {
        $this->_helper->ordering('Name');
    }

    public function miscAction()
    {
    }

    public function userdefinedAction()
    {
        $form = new Form_UserDefinedInfo;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $this->computer->setUserDefinedInfo($form->getValues());

                $session = new Zend_Session_Namespace('UpdateUserdefinedInfo');
                $session->setExpirationHops(1);
                $session->success = true;

                $this->_redirect('computer/userdefined/id/' . $this->computer->getId());
                return;
            }
        } else {
            $form->setDefaults($this->computer->getUserDefinedInfo());
        }
        $this->view->form = $form;
    }

    public function packagesAction()
    {
        $this->_helper->ordering('Name');
    }

    public function groupsAction()
    {
        $this->_helper->ordering('GroupName');
    }

    public function deleteAction()
    {
        $form = new Form_YesNo;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                if ($this->_getParam('yes')) {
                    $session = new Zend_Session_Namespace('ComputerMessages');
                    $session->setExpirationHops(1);
                    $session->computerName = $this->computer->getName();

                    if ($this->computer->delete()) {
                        $session->success = true;
                        $session->message = $this->view->translate(
                            'Computer \'%s\' was successfully deleted.'
                        );
                    } else {
                        $session->success = false;
                        $session->message = $this->view->translate(
                            'Computer \'%s\' could not be deleted.'
                        );
                    }
                    $this->_redirect('computer');
                } else {
                    $this->_redirect('computer/general/id/' . $this->computer->getId());
                }
            }
        } else {
            $this->view->form = $form;
        }
    }

    public function removepackageAction()
    {
        $session = new Zend_Session_Namespace('RemovePackage');

        if ($this->getRequest()->isGet()) {
            $session->setExpirationHops(1);
            $session->packageName = $this->_getParam('name');
            $session->computerId = $this->_getParam('id');
            return; // proceed with view script
        }

        $id = $session->computerId;
        if ($this->_getParam('yes')) {
            $this->computer->unaffectPackage($session->packageName);
        }

        $this->_redirect('computer/packages/id/' . $id);
    }

    public function installpackageAction()
    {
        $computer = $this->computer;
        $form = new Form_AffectPackages;
        $form->addPackages($computer);
        if ($form->isValid($_POST)) {
            $packages = array_keys($form->getValues());
            foreach ($packages as $packageName) {
                $computer->installPackage($packageName);
            }
        }
        $this->_redirect('computer/packages/id/' . $computer->getId());
    }

    public function searchAction()
    {
        $form = new Form_Search;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                // Request minimal column list and add columns for pattern or inverted searches
                $columns = array('Name', 'UserName', 'InventoryDate');
                if ($form->getValue('invert') or !$form->getValue('exact')) {
                    if ($form->getValue('filter') != 'Name') { // Always present; no column to add
                        $columns[] = $form->getValue('filter');
                    }
                }
                // Redirect to index page with all search parameters
                $this->_redirect(
                    'computer/index' . $this->getFilterUriPart() . '/customFilter/1/columns/' . implode(',', $columns)
                );
                return;
            }
        }

        $form->setDefaults($this->_getAllParams());
        // Set form action explicitly to prevent GET parameters leaking into submitted form data
        $form->setAction($this->_helper->url('search'));
        $this->view->form = $form;
    }

    /**
     * Return the part of the URI that defines the current filter
     * @return string URI part, beginning with '/', or empty string if no filter is active.
     */
    public function getFilterUriPart()
    {
        if (!$this->_getParam('filter')) {
            return '';
        }
        $part = '/filter/' . urlencode($this->_getParam('filter'));

        if ($this->_getParam('search')) {
            $part .= '/search/' . urlencode($this->_getParam('search'));
        }

        if ($this->_getParam('exact')) {
            $part .= '/exact/1';
        }

        if ($this->_getParam('invert')) {
            $part .= '/invert/1';
        }

        return $part;
    }

}
