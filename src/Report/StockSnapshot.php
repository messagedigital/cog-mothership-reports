<?php namespace UniformWares\Reports\Report;

use DateTime;
use DateTimeZone;
use UniformWares\Reports\ReportType\TableReport;

class StockSnapshot extends TableReport
{

	public function getView()
	{
		return '::report:stocksnapshot';
	}

	public function getColumns()
	{
		return array(
			'model'       => 'Model',
			'finish'      => 'Finish',
			'stockTotal'  => 'Stock Total',
		);
	}

	public function view($params = array())
	{
		$form = $this->getForm();

		$params['form'] = $form;

		return parent::view($params);
	}

	public function getForm()
	{
		// Create the date filter form
		$form = $this->_controller->get('form');

		// Set the form data if it has been submitted
		$formData = $this->_controller->get('request')->get('form');

		if (! isset($formData['date'])) {
			$formData['date'] = new DateTime("@" . $this->getLastSnapshot());
		}
		else {
			$formData['date'] = new DateTime($formData['date']);
		}

		$form->setDefaultValues($formData);

		$form->add('date', 'date', 'Date', array(
			'widget' => 'single_text'
		));

		return $form;
	}

	public function getData($from = null, $limit = null)
	{
		$query = '
			SELECT
				p.name as model,
				uo.option_value as finish,
				ss.stock as stockTotal,
				p.category as category
			FROM
				product_unit_stock_snapshot ss
			LEFT JOIN
				product_unit u ON u.unit_id = ss.unit_id
			LEFT JOIN
				product p ON u.product_id = p.product_id
			LEFT JOIN
				product_unit_option uo ON uo.unit_id = u.unit_id
			WHERE
				uo.option_name = "colour" AND
				ss.created_at >= ? AND
				ss.created_at <= ?
			GROUP BY
				ss.unit_id
			ORDER BY
				p.category DESC,
				ss.unit_id ASC
		';

		$form = $this->getForm()->getFilteredData();


		$date = (isset($form['date'])) ? $form['date'] : new DateTime("@".$this->getLastSnapshot());

		$date->setTimezone(new DateTimeZone(date_default_timezone_get()));

		$params = array(
			$date->setTime(0, 0, 0)->getTimestamp(),
			$date->setTime(23, 59, 59)->getTimestamp(),
		);

		$rows = $this->_query->run($query, $params);

		$data = array();

		foreach ($rows as $row) {
			if (! isset($data[$row->category])) {
				$data[$row->category] = (object) array(
					'label' => $row->category,
					'total' => 0,
					'models' => array()
				);
			}

			if (! isset($data[$row->category]->models[$row->model])) {
				$data[$row->category]->models[$row->model] = (object) array(
					'label' => $row->model,
					'total' => 0,
					'rows' => array()
				);
			}

			$data[$row->category]->total += $row->stockTotal;
			$data[$row->category]->models[$row->model]->total += $row->stockTotal;
			$data[$row->category]->models[$row->model]->rows[] = $row;
		}

		return $data;
	}

	public function getSnapshots()
	{
		$query = "SELECT DISTINCT(created_at) as created_at FROM product_unit_stock_snapshot";

		return $this->_query->run($query)->flatten('created_at');
	}

	public function getLastSnapshot()
	{
		$query = "
			SELECT created_at
			FROM product_unit_stock_snapshot
			ORDER BY created_at DESC
			LIMIT 1
		";

		return $this->_query->run($query)->value();
	}

	public function getName()
	{
		return "Stock Snapshot";
	}

	public function getDescription()
	{
		return "Stock snapshot over a selected period";
	}

}