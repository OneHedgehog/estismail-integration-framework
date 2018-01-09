<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/29/17
 * Time: 6:17 PM
 */

namespace Integration\Common;


class Config {

	protected $postgres =
		[
			'insales' => [
				'host' => 'localhost',
				'user' => 'root',
				'pas'  => 'Afcj78_hjo5',
				'db'   => 'in_sales'
			],

			'ecwid' => [
				'host' => 'localhost',
				'user' => 'ecwid_app',
				'pas'  => 'Afcj78_hjo5',
				'db'   => 'ecwid_app'
			],

            'moysklad' => [
                'host' => 'localhost',
                'user' => 'moysklad_app',
                'pas'  => 'Afcj78_hjo5',
                'db'   => 'moysklad_app'
            ],

			'amoCRM'=> [
				'host' => 'localhost',
				'user'=> 'amocrm',
				'pas'=>'Afcj78_hjo5',
				'db'=>'amocrm'
			]
		];


}