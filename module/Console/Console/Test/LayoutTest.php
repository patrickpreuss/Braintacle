<?php
/**
 * Tests for the main layout template
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test;

use \Zend\Dom\Document\Query;

/**
 * Tests for the main layout template
 */
class LayoutTest extends \PHPUnit_Framework_TestCase
{
    protected $_view;

    public function setUp()
    {
        $this->_view = new \Zend\View\Renderer\PhpRenderer;
        $this->_view->setHelperPluginManager(clone \Library\Application::getService('ViewHelperManager'));
        $this->_view->setResolver(
            new \Zend\View\Resolver\TemplateMapResolver(
                array('layout' => \Console\Module::getPath('view/layout/layout.php'))
            )
        );
    }

    public function testMinimalLayout()
    {
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $dom = $document->getDomDocument();

        $doctype = $dom->doctype;
        $this->assertEquals('HTML', $doctype->name);
        $this->assertEquals('-//W3C//DTD HTML 4.01//EN', $doctype->publicId);
        $this->assertEquals('http://www.w3.org/TR/html4/strict.dtd', $doctype->systemId);

        $this->assertCount(1, Query::execute('/html', $document));
        $this->assertCount(
            1,
            Query::execute(
                '/html/head/meta[@http-equiv="Content-Type"][@content="text/html; charset=UTF-8"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '/html/head/link[@href="/style.css"][@media="screen"][@rel="stylesheet"][@type="text/css"]',
                $document
            )
        );
    }

    public function testTitle()
    {
        $this->_view->headTitle()->setTranslatorEnabled(false)->append('title');
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/head/title[text()="title"]', $document));
    }

    public function testHeadScript()
    {
        $this->_view->headScript()->appendScript('script');
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/head/script[contains(text(), "script")]', $document));
    }

    public function testBodyOnloadEmpty()
    {
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/body[not(@onload)]', $document));
    }

    public function testBodyOnload1Handler()
    {
        $this->_view->placeholder('BodyOnLoad')->append('onload1');
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/body[@onload="onload1"]', $document));
    }

    public function testBodyOnload2Handlers()
    {
        $this->_view->placeholder('BodyOnLoad')->append('onload1');
        $this->_view->placeholder('BodyOnLoad')->append('onload2');
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('/html/body[@onload="onload1; onload2"]', $document));
    }

    public function testContent()
    {
        $html = $this->_view->render('layout', array('content' => 'content'));
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute("/html/body/div[@id='content'][text()='\ncontent\n']", $document));
    }

    public function testNoIdentity()
    {
        $html = $this->_view->render('layout');
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(0, Query::execute('//div[@id="menu"]', $document));
    }

    public function testIdentityButNoRoute()
    {
        $authService = $this->getMock('Model\Operator\AuthenticationService');
        $authService->method('hasIdentity')->willReturn(true);
        $authService->method('getIdentity')->willReturn('identity');
        $this->_view->plugin('identity')->setAuthenticationService($authService);
        $html = $this->_view->render('layout', array('noRoute' => true));
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(0, Query::execute('//div[@id="menu"]', $document));
    }

    public function testMenu()
    {
        $authService = $this->getMock('Model\Operator\AuthenticationService');
        $authService->method('hasIdentity')->willReturn(true);
        $authService->method('getIdentity')->willReturn('identity');
        $this->_view->plugin('identity')->setAuthenticationService($authService);
        $this->_view->plugin('navigation')->setInjectTranslator(false);

        $menu = \Zend\Navigation\Page\AbstractPage::factory(
            array(
                'type' => 'uri',
                'pages' => array(
                    array(
                        'label' => 'main',
                        'uri' => 'mainUri',
                        'active' => true,
                        'pages' => array(
                            array(
                                'label' => 'sub',
                                'uri' => 'subUri',
                                'active' => true,
                            ),
                        ),
                    ),
                ),
            )
        );
        $html = $this->_view->render('layout', array('menu' => $menu));
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(
            1,
            Query::execute(
                '/html/body/div[@id="menu"]/ul[@class="navigation"]/li/a[@href="mainUri"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '/html/body/div[@id="menu"]/ul[@class="navigation navigation_sub"]/li/a[@href="subUri"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                "/html/body/div[@id='menu']/div[@id='logout']/a[@href='/console/login/logout/'][text()='\nAbmelden\n']",
                $document
            )
        );
    }
}