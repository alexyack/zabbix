<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require_once dirname(__FILE__).'/../include/CWebTest.php';
require_once dirname(__FILE__).'/traits/TableTrait.php';
require_once dirname(__FILE__).'/traits/FilterTrait.php';
require_once dirname(__FILE__).'/../include/helpers/CDataHelper.php';

/**
 * @dataSource TagFilter
 */
class testPageMonitoringHosts extends CWebTest {

	use FilterTrait;
	use TableTrait;

	/**
	 * Id of host that was updated.
	 *
	 * @var integer
	 */
	protected static $hostid;

	public function testPageMonitoringHosts_CheckLayout() {
		$this->page->login()->open('zabbix.php?action=host.view')->waitUntilReady();
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		$table = $this->query('class:list-table')->asTable()->one();

		// Checking Title, Header and Column names.
		$this->page->assertTitle('Hosts');
		$this->page->assertHeader('Hosts');
		$headers = ['Name', 'Interface', 'Availability', 'Tags', 'Status', 'Latest data', 'Problems','Graphs',
				'Dashboards', 'Web'];
		$this->assertSame($headers, ($this->query('class:list-table')->asTable()->one())->getHeadersText());

		// Check filter collapse/expand.
		foreach ([true, false] as $status) {
			$this->assertTrue($this->query('xpath://li[contains(@class, "expanded")]')->one()->isPresent($status));
			$this->query('xpath://a[@aria-label="Home"]')->one()->click();
		}

		// Check fields maximum length.
		foreach(['tags[0][tag]', 'tags[0][value]'] as $field) {
			$this->assertEquals(255, $form->query('xpath:.//input[@name="'.$field.'"]')
				->one()->getAttribute('maxlength'));
		}

		// Check tags maximum length.
		foreach(['name', 'ip', 'dns', 'port'] as $field) {
			$this->assertEquals(255, $form->query('xpath:.//input[@id="'.$field.'_0"]')
				->one()->getAttribute('maxlength'));
		}

		// Check disabled links.
		foreach (['Graphs', 'Dashboards', 'Web'] as $disabled) {
			$row = $table->findRow('Name', 'Available host');
			$this->assertTrue($row->query('xpath://following::td/span[@class="disabled" and text()="'.$disabled.'"]')->exists());
		}

		// Check tags on the specific host.
		$tags = $table->findRow('Name', 'Host for tags filtering - clone')->getColumn('Tags')->query('class:tag')->all();
		$this->assertEquals(['action: clone', 'tag: host'], $tags->asText());

		foreach ($tags as $tag) {
			$tag->click();
			$hint = $this->query('xpath://div[@data-hintboxid]')->asOverlayDialog()->waitUntilPresent()->all()->last();
			$this->assertEquals($tag->getText(), $hint->getText());
			$hint->close();
		}
	}

	public static function getCheckFilterData() {
		return [
			[
				[
					'filter' => [
						'Name' => 'Empty host'
					],
					'expected' => [
						'Empty host'
					]
				]
			],
			[
				[
					'filter' => [
						'Host groups' => [
							'Group to copy all graph'
						]
					],
					'expected' => [
						'Host with item to copy all graphs 1',
						'Host with item to copy all graphs 2'
					]
				]
			],
			[
				[
					'filter' => [
						'IP' => '127.0.0.3'
					],
					'expected' => [
						'Template inheritance test host',
						'Test item host'
					]
				]
			],
			[
				[
					'filter' => [
						'DNS' => 'zabbixzabbixzabbix.com'
					],
					'expected' => [
						'Available host',
						'Not available host',
						'Not available host in maintenance',
						'Unknown host',
						'Unknown host in maintenance'
					]
				]
			],
			[
				[
					'filter' => [
						'Port' => '161'
					],
					'expected' => [
						'Test item host',
						'Visible host for template linkage'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'Not classified'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview',
						'Host for tag permissions'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'Warning'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'High'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'Information'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'Average'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview',
						'3_Host_to_check_Monitoring_Overview',
						'4_Host_to_check_Monitoring_Overview',
						'Host for triggers filtering',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => 'Disaster'
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview'
					]
				]
			],
			[
				[
					'filter' => [
						'Status' => 'Disabled'
					],
					'expected' => [
						'No data found.'
					]
				]
			],
			[
				[
					'filter' => [
						'Name' => 'available',
						'Host groups' => [
							'Group for Host availability widget'
						]
					],
					'expected' => [
						'Available host',
						'Not available host'
					]
				]
			],
			[
				[
					'filter' => [
						'Name' => 'for',
						'Host groups' => [
							'Zabbix servers'
						],
						'IP' => '127.0.5.1'
					],
					'expected' => [
						'Simple form test host'
					]
				]
			],
			[
				[
					'filter' => [
						'Name' => 'Unknown',
						'Host groups' => [
							'Group for Host availability widget'],
						'IP' => '127.0.0.1',
						'DNS' => 'zabbix.com'
					],
					'expected' => [
						'Unknown host'
					]
				]
			],
			[
				[
					'filter' => [
						'Name' => 'maintenance',
						'Host groups' => [
							'Group in maintenance for Host availability widget'
						],
						'IP' => '127.0.0.1',
						'DNS' => 'zab',
						'Port' => '10050'
					],
					'expected' => [
						'Not available host in maintenance',
						'Unknown host in maintenance'
					]
				]
			],
			[
				[
					'filter' => [
						'Severity' => [
							'Not classified',
							'Warning',
							'High',
							'Information',
							'Average',
							'Disaster'
						]
					],
					'expected' => [
						'1_Host_to_check_Monitoring_Overview',
						'3_Host_to_check_Monitoring_Overview',
						'4_Host_to_check_Monitoring_Overview',
						'Host for tag permissions',
						'Host for triggers filtering',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'filter' => [
						'Name' => 'Tommy'
					],
					'expected' => []
				]
			],
			// With name 'maintenance', exists 3 hosts in maintenance status. Unchecking 'Show hosts in maintenance'.
			[
				[
					'filter' => [
						'Name' => 'maintenance',
						'Show hosts in maintenance' => false
					],
					'expected' => []
				]
			],
			[
				[
					'filter' => [
						'Name' => 'maintenance'
					],
					'expected' => [
						'Available host in maintenance',
						'Not available host in maintenance',
						'Unknown host in maintenance'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getCheckFilterData
	 */
	public function testPageMonitoringHosts_CheckFilter($data) {
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1');
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		$form->fill($data['filter']);
		$result_form = $this->query('xpath://form[@name="host_view"]')->one();
		$this->query('button:Apply')->waitUntilClickable()->one()->click();
		$this->page->waitUntilReady();
		$result_form->waitUntilReloaded();
		$this->assertTableDataColumn($data['expected']);
	}

	public static function getTagsFilterData() {
		return [
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'value' => 'test_tag', 'operator' => 'Equals']
						]
					],
					'result' => [
						'Host for tags filtering'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'value' => '', 'operator' => 'Contains']
						]
					],
					'result' => [
						'Host for tags filtering'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'test', 'value' => 'test_tag', 'operator' => 'Equals'],
							['name' => 'action', 'value' => 'clone', 'operator' => 'Contains']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - clone'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'action', 'value' => 'clone', 'operator' => 'Equals'],
							['name' => 'tag', 'value' => 'host', 'operator' => 'Equals']
						]
					],
					'result' => [
						'Host for tags filtering - clone',
						'Host for tags filtering - update'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'action', 'value' => 'clone', 'operator' => 'Contains'],
							['name' => 'tag', 'value' => 'host', 'operator' => 'Contains']
						]
					],
					'result' => [
						'Host for tags filtering - clone'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'action', 'value' => 'clone', 'operator' => 'Equals'],
							['name' => 'action', 'value' => 'update', 'operator' => 'Equals'],
							['name' => 'tag', 'value' => 'TEMPLATE', 'operator' => 'Equals']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - clone',
						'Host for tags filtering - update'
					]
				]
			],
			// Wrote 'template' in lowercase.
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'tag', 'value' => 'template', 'operator' => 'Equals']
						]
					],
					'result' => []
				]
			],
			// Non-existing tag.
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'Tommy', 'value' => 'train', 'operator' => 'Contains']
						]
					],
					'result' => []
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Exists']
						]
					],
					'result' => [
						'Host for tags filtering'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Exists']
						]
					],
					'result' => [
						'Host for tags filtering'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'tag', 'operator' => 'Exists'],
							['name' => 'test', 'operator' => 'Exists']
						]
					],
					'result' => [
						'Host for tags filtering'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'tag', 'operator' => 'Exists'],
							['name' => 'test', 'operator' => 'Exists']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - clone',
						'Host for tags filtering - update'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not exist']
						]
					],
					'result' => [
						'Host for tags filtering - clone',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not exist']
						]
					],
					'result' => [
						'Host for tags filtering - clone',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not exist'],
							['name' => 'tag', 'operator' => 'Does not exist']
						]
					],
					'result' => [
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not exist'],
							['name' => 'tag', 'operator' => 'Does not exist']
						]
					],
					'result' => [
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not equal', 'value' => 'test_tag']
						]
					],
					'result' => [
						'Host for tags filtering - clone',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not equal', 'value' => 'test_tag']
						]
					],
					'result' => [
						'Host for tags filtering - clone',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not equal', 'value' => 'test_tag'],
							['name' => 'action', 'operator' => 'Does not equal', 'value' => 'clone']
						]
					],
					'result' => [
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'test', 'operator' => 'Does not equal', 'value' => 'test_tag'],
							['name' => 'action', 'operator' => 'Does not equal', 'value' => 'clone']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - clone',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'tag', 'operator' => 'Does not contain', 'value' => 'host']
						]
					],
					'result' => [
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'tag', 'operator' => 'Does not contain', 'value' => 'host']
						]
					],
					'result' => [
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not contain', 'value' => 'clone'],
							['name' => 'tag', 'operator' => 'Does not contain', 'value' => 'host']
						]
					],
					'result' => [
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not contain', 'value' => 'clone'],
							['name' => 'tag', 'operator' => 'Does not contain', 'value' => 'host']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - update',
						'Simple form test host',
						'SLA reports host',
						'Template inheritance test host',
						'ЗАББИКС Сервер'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not contain', 'value' => 'clone'],
							['name' => 'tag', 'operator' => 'Equals', 'value' => 'host']
						]
					],
					'result' => [
						'Host for tags filtering - update'
					]
				]
			],
			[
				[
					'tag_options' => [
						'type' => 'And/Or',
						'tags' => [
							['name' => 'action', 'operator' => 'Does not contain', 'value' => 'clone'],
							['name' => 'tag', 'operator' => 'Exists']
						]
					],
					'result' => [
						'Host for tags filtering',
						'Host for tags filtering - update'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getTagsFilterData
	 */
	public function testPageMonitoringHosts_TagsFilter($data) {
		$this->page->login()->open('zabbix.php?port=10051&action=host.view&groupids%5B%5D=4');
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		$form->fill(['id:evaltype_0' => $data['tag_options']['type']]);
		$this->setFilterSelector('id:tags_0');
		$this->setTags($data['tag_options']['tags']);
		$this->query('button:Apply')->one()->waitUntilClickable()->click();
		$this->page->waitUntilReady();
		$this->assertTableDataColumn(CTestArrayHelper::get($data, 'result', []));
	}

	public function testPageMonitoringHosts_ResetButtonCheck() {
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1');
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		$this->page->waitUntilReady();
		$table = $this->query('class:list-table')->asTable()->one();

		// Check table contents before filtering.
		$start_rows_count = $table->getRows()->count();
		$this->assertTableStats($start_rows_count);
		$start_contents = $this->getTableResult('Name');

		// Filter hosts.
		$form->fill(['Name' => 'Empty host']);
		$this->query('button:Apply')->one()->waitUntilClickable()->click();
		$this->page->waitUntilReady();

		// Check that filtered count matches expected.
		$this->assertEquals(1, $table->getRows()->count());
		$this->assertTableStats(1);

		// After pressing reset button, check that previous hosts are displayed again.
		$this->query('button:Reset')->one()->click();
		$this->page->waitUntilReady();
		$reset_rows_count = $table->getRows()->count();
		$this->assertEquals($start_rows_count, $reset_rows_count);
		$this->assertTableStats($reset_rows_count);
		$this->assertEquals($start_contents, $this->getTableResult('Name'));
	}

	// Checking that Show suppressed problems filter works.
	public function testPageMonitoringHosts_ShowSuppresed() {
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1');
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		$this->page->waitUntilReady();
		$table = $this->query('class:list-table')->asTable()->one();
		$form->fill(['Severity' => ['Not classified', 'Information', 'Warning', 'Average', 'High', 'Disaster']]);
		$this->query('button:Apply')->one()->waitUntilClickable()->click();
		$this->page->waitUntilReady();
		foreach ([true, false] as $show) {
			$form->query('id:show_suppressed_0')->asCheckbox()->one()->fill($show);
			$this->query('button:Apply')->one()->waitUntilClickable()->click();
			$this->page->waitUntilReady();
			$this->assertTrue($table->findRow('Name', 'Host for suppression')->isPresent($show));
		}
	}

	public static function getEnabledLinksData() {
		return [
			[
				[
					'name' => 'Dynamic widgets H1',
					'link_name' => 'Graphs',
					'page_header' => 'Graphs'
				]
			],
			[
				[
					'name' => 'Host ZBX6663',
					'link_name' => 'Web',
					'page_header' => 'Web monitoring'
				]
			],
			[
				[
					'name' => 'ЗАББИКС Сервер',
					'link_name' => 'Dashboards',
					'page_header' => 'Network interfaces'
				]
			],
			[
				[
					'name' => 'Empty host',
					'link_name' => 'Problems',
					'page_header' => 'Problems'
				]
			],
			[
				[
					'name' => 'Available host',
					'link_name' => 'Latest data',
					'page_header' => 'Latest data'
				]
			]
		];
	}

	/**
	 * @dataProvider getEnabledLinksData
	 *
	 * Check enabled links and that correct host is displayed.
	 */
	public function testPageMonitoringHosts_EnabledLinks($data) {
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1');
		$form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
		switch ($data['name']) {
			case 'Dynamic widgets H1':
			case 'Host ZBX6663':
			case 'Available host':
				$this->selectLink($data['name'], $data['link_name'], $data['page_header']);
				$this->page->waitUntilReady();
				$filter_form = $this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one();
				$filter_form->checkValue(['Hosts' => $data['name']]);
				$this->query('button:Reset')->one()->click();
				break;
			case 'ЗАББИКС Сервер':
				$this->selectLink($data['name'], $data['link_name'], $data['page_header']);
				break;
			case 'Empty host':
				$this->page->waitUntilReady();
				$this->query('xpath://td/a[text()="'.$data['name'].'"]/following::td/a[text()="'.$data['link_name'].'"]')
					->one()->click();
				$this->page->waitUntilReady();
				$this->page->assertHeader($data['page_header']);
				$form->checkValue(['Hosts' => $data['name']]);
				$this->query('button:Reset')->one()->click();
				break;
		}
	}

	public static function getHostContextMenuData() {
		return [
			[
				[
					'name' => 'ЗАББИКС Сервер',
					'disabled' => ['Web'],
					'titles' => [
						'Inventory',
						'Latest data',
						'Problems',
						'Graphs',
						'Dashboards',
						'Web',
						'Configuration',
						'Detect operating system',
						'Ping',
						'Script for Clone',
						'Script for Delete',
						'Script for Update',
						'Traceroute'
					]
				]
			],
			[
				[
					'name' => 'Available host',
					'disabled' => ['Web', 'Graphs', 'Dashboards'],
					'titles' => [
						'Inventory',
						'Latest data',
						'Problems',
						'Graphs',
						'Dashboards',
						'Web',
						'Configuration',
						'Detect operating system',
						'Ping',
						'Script for Clone',
						'Script for Delete',
						'Script for Update',
						'Traceroute'
					]
				]
			],
			[
				[
					'name' => 'Dynamic widgets H1',
					'disabled' => ['Dashboards', 'Web'],
					'titles' => [
						'Inventory',
						'Latest data',
						'Problems',
						'Graphs',
						'Dashboards',
						'Web',
						'Configuration',
						'Detect operating system',
						'Ping',
						'Script for Clone',
						'Script for Delete',
						'Script for Update',
						'Traceroute'
					]
				]
			],
			[
				[
					'name' => 'Host ZBX6663',
					'disabled' => ['Dashboards'],
					'titles' => [
						'Inventory',
						'Latest data',
						'Problems',
						'Graphs',
						'Dashboards',
						'Web',
						'Configuration',
						'Detect operating system',
						'Ping',
						'Script for Clone',
						'Script for Delete',
						'Script for Update',
						'Traceroute'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getHostContextMenuData
	 *
	 * Click on host name from the table and check displayed popup context.
	 */
	public function testPageMonitoringHosts_HostContextMenu($data) {
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1')->waitUntilReady();
		$row = $this->query('class:list-table')->asTable()->one()->findRow('Name', $data['name']);
		$row->query('link', $data['name'])->one()->click();
		$this->page->waitUntilReady();
		$popup = CPopupMenuElement::find()->waitUntilVisible()->one();
		$this->assertEquals(['HOST', 'SCRIPTS'], $popup->getTitles()->asText());
		$this->assertTrue($popup->hasItems($data['titles']));
		foreach ($data['disabled'] as $disabled) {
			$this->assertTrue($popup->query('xpath://a[@aria-label="Host, '.
					$disabled.'" and @class="menu-popup-item disabled"]')->one()->isPresent());
		}
	}

	/**
	 * Check number of problems displayed on Hosts and Problems page.
	 */
	public function testPageMonitoringHosts_CountProblems() {
		$this->page->login();
		$hosts_names = ['1_Host_to_check_Monitoring_Overview', 'ЗАББИКС Сервер', 'Host for tag permissions', 'Empty host'];
		foreach ($hosts_names as $host) {
			$this->page->open('zabbix.php?action=host.view&name='.$host)->waitUntilReady();
			$table = $this->query('class:list-table')->asTable()->one();

			// Get number of problems displayed on icon and it severity level.
			if ($host !== 'Empty host') {
				$icons = $table->query('xpath:.//*[contains(@class, "problem-icon-list-item")]')->all();
				$results = [];

				foreach ($icons as $icon) {
					$amount = $icon->getText();
					$severity = $icon->getAttribute('title');
					$results[$severity] = $amount;
				}
			}
			else {
				$this->assertEquals('Problems', $table->getRow(0)->getColumn('Problems')->getText());
			}

			// Navigate to Problems page from Hosts.
			$table->getRow(0)->getColumn('Problems')->query('xpath:.//a')->one()->click();
			$this->page->waitUntilReady();
			$this->page->assertTitle('Problems');
			$this->query('name:zbx_filter')->waitUntilPresent()->asForm()->one()->checkValue(['Hosts' => $host]);

			// Count problems of each severity and compare it with problems count from Hosts page.
			if ($host !== 'Empty host') {
				foreach ($results as $severity => $count) {
					$problem_count = $table->query('xpath:.//td[contains(@class, "-bg") and text()="'.$severity.'"]')
							->all()->count();
					$this->assertEquals(strval($problem_count), $count);
				}
			}

			// Check that table is empty and No data found displayed.
			else {
				$this->assertTableData();
			}
		}
	}

	public function prepareUpdateData() {
		$response = CDataHelper::call('host.update', ['hostid' => '99013', 'status' => 1]);
		$this->assertArrayHasKey('hostids', $response);
		self::$hostid = $response['hostids'][0];
	}

	/**
	 * @backup hosts
	 *
	 * @onBeforeOnce prepareUpdateData
	 */
	public function testPageMonitoringHosts_TableSorting() {
		// Sort by name and status.
		$this->page->login()->open('zabbix.php?action=host.view&filter_rst=1')->waitUntilReady();
		foreach (['Name', 'Status'] as $listing) {
			$query = $this->query('xpath://a[@href and text()="'.$listing.'"]');
			$query->one()->click();
			$this->page->waitUntilReady();
			$after_listing = $this->getTableResult($listing);
			$query->one()->click();
			$this->page->waitUntilReady();
			$this->assertEquals(array_reverse($after_listing), $this->getTableResult($listing));
		}
	}

	/**
	 * Clicking on link from the table and then checking page header
	 *
	 * @param string $host_name		Host name
	 * @param string $column		Column name
	 * @param string $page_header	Page header name
	 */
	private function selectLink($host_name, $column, $page_header) {
		$this->page->waitUntilReady();
		$this->query('class:list-table')->asTable()->one()->findRow('Name', $host_name)->getColumn($column)->click();
		$this->page->waitUntilReady();
		if ($page_header !== null) {
			$this->page->assertHeader($page_header);
		}
		if ($host_name === 'Dynamic widgets H1' && $this->query('xpath://li[@aria-labelledby="ui-id-2"'.
				' and @aria-selected="false"]')->exists()) {
			$this->query('id:ui-id-2')->one()->click();
		}
		if ($host_name === 'ЗАББИКС Сервер' && $column === 'Dashboards') {
			$this->assertEquals('ЗАББИКС Сервер', $this->query('xpath://ul[@class="breadcrumbs"]/li[2]')->one()->getText());
		}
	}
}
