<?php

App::uses('SeedAppShell', 'Seed.Console/Command');

class SeedShell extends SeedAppShell {
	public $seedProdFile = 'seed_prod.php';
	public $seedDevFile = 'seed_dev.php';

	public function main()
	{
		$this->includeFile($this->absolutePath($this->getFile()));
	}

	public function init()
	{
		$this->existsOrCreate($this->absolutePath($this->getFile()));
	}

	public function getOptionParser()
	{
		$parser = parent::getOptionParser();
		$parser->addOption('dev', array(
			'boolean' => true,
			'help' => 'Use the default dev file instead of the default'
		));
		$parser->addOption('file', array(
			'help' => 'Manually specify the file that should be used'
		));
		return $parser;
	}

	public function seed($Model, $data, $conditions = array())
	{
		$record = $Model->find('first', array('conditions' => $conditions));
		if (empty($record)) {
			$Model->create($data + $conditions);
			if ($Model->save()) {
				$record = $Model->read();
			} else {
				$cond = var_export($conditions, true);
				$this->out("Failed to create {$Model->alias} record for conditions:\n\n{$cond}");
				if (!empty($Model->validationErrors)) {
					$validationErrors = var_export($Model->validationErrors, true);
					$this->out("Validation errors encountered:\n\n{$validationErrors}");
				}
				exit();
			}
		} else {
			if (!empty($conditions)) {
				$db = $Model->getDataSource();
				$updateData = array();
				$conditionData = array();
				foreach ($data as $key => $val) {
					$modelKey = $Model->name.'.'.$key;
					$updateData[$modelKey] = $db->value($val, gettype($val));
				}
				foreach ($conditions as $key => $val) {
					$modelKey = $Model->name.'.'.$key;
					$conditionData[$modelKey] = $db->value($val, gettype($val));
				}
				if ($Model->updateAll($updateData, $conditionData)) {
					$record = $Model->read();
				} else {
					$cond = var_export($conditions, true);
					$this->out("Failed to update {$Model->alias} record for conditions:\n\n{$cond}");
					if (!empty($Model->validationErrors)) {
						$validationErrors = var_export($Model->validationErrors, true);
						$this->out("Validation errors encountered:\n\n{$validationErrors}");
					}
					exit();
				}
			}
		}
		return $record;
	}

	private function getFile()
	{
		$file = $this->seedProdFile;
		if (isset($this->params['file']) && !empty($this->params['file'])) {
			$file = $this->params['file'];
		} else if ($this->params['dev']) {
			$file = $this->seedDevFile;
		}
		return $file;
	}

	private function includeFile($file)
	{
		include $file;
	}

	private function existsOrCreate($file)
	{
		if (!file_exists($file)) {
			file_put_contents($file, "<?php\n\n");
		}
	}

	private function absolutePath($file)
	{
		return APP . 'Config' . DS . $file;
	}
}

