<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportBoardType extends AbstractType
{
	public function buildForm(FormBuilderInterface $formBuilder, array $options): void
	{
		$formBuilder
			->add('repository', ChoiceType::class, ['label' => 'board.export.repository', 'choices' => $options['repository'], 'choice_translation_domain' => false])
			->add('dateMin', DateType::class, ['label' => 'board.export.dateMin', 'data' => new \DateTime('first day of this month'), 'required' => false])
			->add('dateMax', DateType::class, ['label' => 'board.export.dateMax', 'data' => new \DateTime('last day of this month'), 'required' => false])
			->add('week', CheckboxType::class, ['label' => 'board.export.week', 'required' => false])
			->add('export', SubmitType::class, ['label' => 'board.export.export']);
	}

	public function configureOptions(OptionsResolver $optionsResolver): void
	{
		$optionsResolver->setDefaults([
			'repository' => [],
		]);

		$optionsResolver->setAllowedTypes('repository', 'array');
	}
}
