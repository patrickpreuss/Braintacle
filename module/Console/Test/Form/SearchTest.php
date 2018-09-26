<?php
/**
 * Tests for Search form
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\Form;

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for Search form
 */
class SearchTest extends \Console\Test\AbstractFormTest
{
    /**
     * RegistryManager mock object
     * @var \Model\Registry\RegistryManager
     */
    protected $_registryManager;

    /**
     * CustomFieldManager mock object
     * @var \Model\Client\CustomFieldManager
     */
    protected $_customFieldManager;

    public function setUp()
    {
        $resultSet = new \Zend\Db\ResultSet\ResultSet();
        $resultSet->initialize(array(array('Name' => 'RegValue')));
        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_registryManager->expects($this->once())
                               ->method('getValueDefinitions')
                               ->willReturn($resultSet);
        $this->_customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $this->_customFieldManager->expects($this->once())
                            ->method('getFields')
                            ->will(
                                $this->returnValue(
                                    array(
                                        'TAG' => 'text',
                                        'Clob' => 'clob',
                                        'Integer' => 'integer',
                                        'Float' => 'float',
                                        'Date' => 'date',
                                    )
                                )
                            );
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => new \Zend\Mvc\I18n\Translator(new \Zend\Mvc\I18n\DummyTranslator),
                'registryManager' => $this->_registryManager,
                'customFieldManager' => $this->_customFieldManager,
            )
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $filter = $this->_form->get('filter');
        $this->assertInstanceOf('Zend\Form\Element\Select', $filter);
        $filters = $filter->getValueOptions();
        $this->assertContains('Software: Name', $filters); // Hardcoded
        $this->assertContains('Registry: RegValue', $filters); // dynamically added
        $this->assertContains('User defined: Category', $filters); // dynamically added; renamed
        $this->assertContains('User defined: Integer', $filters); // dynamically added

        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('search'));
        $this->assertInstanceOf('Zend\Form\Element\Select', $this->_form->get('operator'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $this->_form->get('invert'));
    }

    public function testInitInvalidDatatype()
    {
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize(new \EmptyIterator);
        $registryManager = $this->createMock('Model\Registry\RegistryManager');
        $registryManager->method('getValueDefinitions')->willReturn($resultSet);
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('getFields')->willReturn(array('test' => 'invalid'));
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => new \Zend\Mvc\I18n\Translator(new \Zend\Mvc\I18n\DummyTranslator),
                'registryManager' => $registryManager,
                'customFieldManager' => $customFieldManager,
            )
        );
        $this->expectException('UnexpectedValueException');
        $form->init();
    }

    public function testSetDataText()
    {
        $data = array(
            'filter' => 'CustomFields.TAG',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
    }

    public function testSetDataClob()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
    }

    public function testSetDataInteger()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => '1234',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.234', $this->_form->get('search')->getValue());

        $data['search'] = '1.234';
        $this->_form->setData($data);
        $this->assertEquals('1.234', $this->_form->get('search')->getValue());

        $data['search'] = '1,234';
        $this->_form->setData($data);
        $this->assertEquals('1,234', $this->_form->get('search')->getValue());
    }

    public function testSetDataFloat()
    {
        $data = array(
            'filter' => 'CustomFields.Float',
            'search' => '1234.5678',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.234,5678', $this->_form->get('search')->getValue());

        $data['search'] = '1.234,5678';
        $this->_form->setData($data);
        $this->assertEquals('1.234,5678', $this->_form->get('search')->getValue());

        $data['search'] = '1,234.5678';
        $this->_form->setData($data);
        $this->assertEquals('1,234.5678', $this->_form->get('search')->getValue());
    }

    public function testSetDataDate()
    {
        $data = array(
            'filter' => 'CustomFields.Date',
            'search' => new \DateTime('2014-05-01'),
        );
        $this->_form->setData($data);
        $this->assertEquals('01.05.2014', $this->_form->get('search')->getValue());

        $data['search'] = '2014-05-01';
        $this->_form->setData($data);
        $this->assertEquals('01.05.2014', $this->_form->get('search')->getValue());

        $data['search'] = '05/01/2014';
        $this->_form->setData($data);
        $this->assertEquals('05/01/2014', $this->_form->get('search')->getValue());
    }

    public function testSetDataNoSearch()
    {
        $data = array(
            'filter' => 'CustomFields.TAG',
        );
        $this->_form->setData($data); // Must not generate error
    }

    public function testInputFilterText()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => ' test ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(' test ', $this->_form->getData()['search']);

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInteger()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => ' 1234 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['search']);

        $data['search'] = '1.234';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['search']);

        $data['search'] = '1,234';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterFloat()
    {
        $data = array(
            'filter' => 'CustomFields.Float',
            'search' => ' 1234,5678 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234.5678, $this->_form->getData()['search']);

        $data['search'] = '1.234,5678';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234.5678, $this->_form->getData()['search']);

        $data['search'] = '1,234.5678';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterDate()
    {
        $data = array(
            'filter' => 'CustomFields.Date',
            'search' => ' 1.5.2014 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('2014-05-01', $this->_form->getData()['search']->format('Y-m-d'));

        $data['search'] = '2014-05-01';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('2014-05-01', $this->_form->getData()['search']->format('Y-m-d'));

        $data['search'] = '05/01/2014';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnTextOperator()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => '1',
            'operator' => 'like',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'ne';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnOrdinalOperator()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => '1',
            'operator' => 'ne',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'like';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInvalidFilter()
    {
        $this->expectException('InvalidArgumentException', 'Invalid filter: invalidFilter');
        $this->_form->setData(array('filter' => 'invalidFilter'));
    }

    public function testMissingFilterOnSearchValidation()
    {
        $this->expectException('LogicException', 'No filter submitted');
        $this->_form->validateSearch('value', array('search' => 'value'));
    }

    public function testMissingFilterOnOperatorValidation()
    {
        $this->expectException('LogicException', 'No filter submitted');
        $this->_form->validateOperator('value', array('search' => 'value'));
    }

    public function testRender()
    {
        $view = $this->_createView();
        $document = new \Zend\Dom\Document(
            $this->_form->render($view)
        );

        $result = Query::execute(
            '//select[@name="filter"][@onchange="filterChanged();"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="search"][@type="text"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//select[@name="operator"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="invert"][@type="checkbox"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="customSearch"][@type="submit"]',
            $document
        );
        $this->assertCount(1, $result);

        $headScript = $view->headScript()->toString();
        $this->assertContains('function filterChanged(', $headScript);
        $this->assertContains(
            'filterChanged()',
            $view->placeholder('BodyOnLoad')->getValue()
        );
    }

    public function testRenderNoPreset()
    {
        $view = $this->_createView();
        $this->_form->render($view);
        $this->assertNotContains(
            'document.getElementById("form_search").elements["operator"].value = "eq"',
            $view->placeholder('BodyOnLoad')->getValue()
        );
    }

    public function testRenderWithPreset()
    {
        $this->_form->get('operator')->setValue('eq');
        $view = $this->_createView();
        $this->_form->render($view);
        $this->assertContains(
            'document.getElementById("form_search").elements["operator"].value = "eq"',
            $view->placeholder('BodyOnLoad')->getValue()
        );
    }
}
