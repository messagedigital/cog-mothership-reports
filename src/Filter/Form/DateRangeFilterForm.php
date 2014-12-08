<?php

namespace Message\Mothership\Report\Filter\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class DateRangeFilterForm extends AbstractType
{
	public function getName()
	{
		return 'filter_date_form';
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('startdate', 'date', ['label' => 'from']);
		$builder->add('enddate', 'date', ['label' => 'to']);
	}
}