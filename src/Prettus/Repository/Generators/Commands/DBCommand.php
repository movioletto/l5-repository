<?php

namespace Prettus\Repository\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DBCommand extends Command
{

	/**
	 * The name of command.
	 *
	 * @var string
	 */
	protected $name = 'make:dbAll';

	/**
	 * The description of command.
	 *
	 * @var string
	 */
	protected $description = 'Create all by db.';

	/**
	 * @var Collection
	 */
	protected $generators = null;

	protected $tipiDiDato = array(
		array("bigint", "bigInteger"),
		array("blob", "binary"),
		array("int", "integer"),
		array("ip", "ipAddress"),
		array("varchar", "string")
	);

	/**
	 * Execute the command.
	 *
	 * @see fire()
	 * @return void
	 */
	public function handle()
	{
		$this->laravel->call([$this, 'fire'], func_get_args());
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$isPresenter = false;
		$isValidator = false;
		$isController = false;
//
//		if ($this->confirm('Would you like to create a Presenter? [y|N]')) {
//			$isPresenter = true;
//		}

		$validator = $this->option('validator');
		if (is_null($validator) && $this->confirm('Would you like to create a Validator? [y|N]')) {
			$isValidator = true;
		}

		if ($this->confirm('Would you like to create a Controller? [y|N]')) {
			$isController = true;
		}


		$queryTabelleDB = json_decode(json_encode(DB::select("SHOW TABLES")), true);
		$tabelleDB = [];
		foreach ($queryTabelleDB as $t) {
			list($chiave) = array_keys($t);
			$tabelleDB[] = $t[$chiave];
		}
		$this->info("Lista delle tabelle: " . implode($tabelleDB, ", \n                     "));


		foreach ($tabelleDB as $t) {
			$tabella = [];
			$tabella['name'] = camel_case($t);
//			$tabella['modelName'] = camel_case($t);
//			$tabella['modelName'] = 'create_' . $t;
			$tabella['tableName'] = $t;
//			$tabella['routeName'] = snake_case($t, '-');
//			$tabella['controllerNamespace'] = '';
//			$tabella['modelNamespace'] = '';

			$this->info("NOME: " . $tabella['name']);

			$queryCreateTabella = json_decode(json_encode(DB::select("SHOW CREATE TABLE " . $tabella['tableName'])), true);
			$createTabella = $this->substringDalFondo($queryCreateTabella[0]["Create Table"], '(', ')', 1);

//			$tabella['primaryKey'] = $this->getPrimaryKey($createTabella);
//
//			if ($tabella['primaryKey'] == null || $tabella['primaryKey'] == "")
//				exit("errore niente primaryKey");
//
//			$this->info("PRIMARYkEY: " . $tabella['primaryKey']);

			$tabella['fields'] = $this->getFields($createTabella);

			$this->info("FIELDS: " . $tabella['fields']);


			if ($isPresenter) {
				$this->call('make:presenter', [
					'name' => $tabella['name'],
					'--force' => $this->option('force'),
				]);
			}

			if ($isValidator) {
				$this->call('make:validator', [
					'name' => $tabella['name'],
//					'--rules' => $this->option('rules'),
					'--force' => $this->option('force'),
				]);
			}

			if ($isController) {

				$resource_args = [
					'name' => $tabella['name']
				];

				// Generate a controller resource
				$controller_command = ((float)app()->version() >= 5.5 ? 'make:rest-controller' : 'make:resource');
				$this->call($controller_command, $resource_args);
			}


			$this->call('make:repository', [
				'name' => $tabella['name'],
				'--fillable' => $tabella['fields'],
				'--rules' => $this->option('rules'),
				'--validator' => $validator,
				'--force' => $this->option('force'),
				'--skip-migration' => true,
				'--skip-model' => true
			]);

			$this->call('make:bindings', [
				'name' => $tabella['name'],
				'--force' => $this->option('force')
			]);

			$this->info("--------------------------------------------------------");
		}

//
//		if ($this->confirm('Would you like to create a Presenter? [y|N]')) {
//			$this->call('make:presenter', [
//				'name' => $this->argument('name'),
//				'--force' => $this->option('force'),
//			]);
//		}
//
//		$validator = $this->option('validator');
//		if (is_null($validator) && $this->confirm('Would you like to create a Validator? [y|N]')) {
//			$validator = 'yes';
//		}
//
//		if ($validator == 'yes') {
//			$this->call('make:validator', [
//				'name' => $this->argument('name'),
//				'--rules' => $this->option('rules'),
//				'--force' => $this->option('force'),
//			]);
//		}
//
//		if ($this->confirm('Would you like to create a Controller? [y|N]')) {
//
//			$resource_args = [
//				'name' => $this->argument('name')
//			];
//
//			// Generate a controller resource
//			$controller_command = ((float)app()->version() >= 5.5 ? 'make:rest-controller' : 'make:resource');
//			$this->call($controller_command, $resource_args);
//		}
//
//		$this->call('make:repository', [
//			'name' => $this->argument('name'),
//			'--fillable' => $this->option('fillable'),
//			'--rules' => $this->option('rules'),
//			'--validator' => $validator,
//			'--force' => $this->option('force')
//		]);
//
//		$this->call('make:bindings', [
//			'name' => $this->argument('name'),
//			'--force' => $this->option('force')
//		]);
	}


	/**
	 * The array of command arguments.
	 *
	 * @return array
	 */
	public function getArguments()
	{
		return [
			[
				'table',
				InputArgument::OPTIONAL,
				'The name of table being generated.',
				null
			],
		];
	}


	/**
	 * The array of command options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return [
			[
				'fillable',
				null,
				InputOption::VALUE_OPTIONAL,
				'The fillable attributes.',
				null
			],
			[
				'rules',
				null,
				InputOption::VALUE_OPTIONAL,
				'The rules of validation attributes.',
				null
			],
			[
				'validator',
				null,
				InputOption::VALUE_OPTIONAL,
				'Adds validator reference to the repository.',
				null
			],
			[
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Force the creation if file already exists.',
				null
			]
		];
	}

	/**
	 * Substring di una stringa partendo da due carattere, uno di inizio e uno di fine.
	 *
	 * @param  string $string
	 * @param  string $stringInizio
	 * @param  string $stringFine
	 * @param  int $offsetInizio
	 * @param  int $offsetRicerca
	 *
	 * @return string
	 */
	protected function substring($string, $stringInizio, $stringFine, $offsetInizio = null, $offsetRicerca = 0)
	{
		if ($offsetInizio == null)
			$offsetInizio = strlen($stringInizio);

		if ($offsetRicerca > 0)
			$string = substr($string, $offsetRicerca);

		$inizio = strpos($string, $stringInizio) + $offsetInizio;

		$ricercaFinale = substr($string, $inizio, strlen($string) - $inizio);
		$fine = strpos($ricercaFinale, $stringFine);

		return substr($string, $inizio, $fine);
	}

	/**
	 * Substring di una stringa partendo da due carattere, uno di inizio e uno di fine.
	 *
	 * @param  string $string
	 * @param  string $stringInizio
	 * @param  string $stringFine
	 * @param  int $offsetInizio
	 * @param  int $offsetRicerca
	 *
	 * @return string
	 */
	protected function substringDalFondo($string, $stringInizio, $stringFine, $offsetInizio = null, $offsetRicerca = 0)
	{
		if ($offsetInizio == null)
			$offsetInizio = strlen($stringInizio);

		if ($offsetRicerca > 0)
			$string = substr($string, $offsetRicerca);

		$inizio = strpos($string, $stringInizio) + $offsetInizio;

		$fine = strrpos($string, $stringFine);

		return substr($string, $inizio, $fine - $inizio);
	}

	/**
	 * Trova la primaryKey da una stringa
	 *
	 * @param  string $string
	 *
	 * @return string
	 */
	protected function getPrimaryKey($string)
	{
		if (strpos($string, 'PRIMARY KEY') !== false) {
			return $this->substring($string, 'PRIMARY KEY (`', '`)');
		}

		return null;
	}

	/**
	 * Trova tutte le colonne della tabella
	 *
	 * @param  string $string
	 *
	 * @return string
	 */
	private function getFields($string)
	{
		/*
		 * @var string
		 */
		$return = "";

		foreach (explode(",", $string) as $parte) {
			if (trim($parte)[0] == '`') {
				$return .= $this->getNameOfField(trim($parte)) . ','; //. ":" . $this->getTypeOfField(trim($parte)) . ",";
			}
		}

		return substr($return, 0, strlen($return) - 1);
	}

	/**
	 * Estrapola il nome del campo
	 *
	 * @param string $parte
	 *
	 * @return string
	 */
	private function getNameOfField($parte)
	{
		$parte = explode(" ", $parte);
		return str_replace("`", "", $parte[0]);
	}

	/**
	 * Estrapola e converte il tipo del campo
	 *
	 * @param  string $parte
	 *
	 * @return string
	 */
	private function getTypeOfField($parte)
	{
		$parte = explode(" ", $parte);
		$tipo = $parte[1];
		if (strpos($tipo, '(') !== false) {
			$tipo = substr($tipo, 0, strpos($tipo, '('));
		}

		return $this->convertType($tipo);
	}

	/**
	 * Converte il tipo del campo
	 *
	 * @param string $tipo
	 *
	 * @return string
	 */
	private function convertType($tipo)
	{
		foreach ($this->tipiDiDato as $dato) {
			if ($dato[0] == $tipo) {
				return $dato[1];
			}
		}

		return $tipo;
	}

}
