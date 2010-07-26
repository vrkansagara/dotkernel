<?php
/**
* DotBoost Technologies Inc.
* DotKernel Application Framework
*
* @category   DotKernel
* @package    Frontend
* @copyright  Copyright (c) 2009 DotBoost  Technologies (http://www.dotboost.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version    $Id$
*/

/**
* Sample Controller
* @author     DotKernel Team <team@dotkernel.com>
*/

$sampleModel = new Sample();
$sampleView = new Sample_View();

//prepare feed
$feed = array(
			'title' => $option->feed->title,
			'link' => $config->website->params->url.$option->feed->link,
			'charset' => $option->feed->charset,
			'language' => $option->feed->language
			);
$feed['published'] = time();
$entries = $sampleModel->setEntries();
$feed['entries'] = $entries;
//set the feed that will be outputed
$view->setFeed($feed);