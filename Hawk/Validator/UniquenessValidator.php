<?php
namespace Hawk\Validator;

use Hawk\Database;
use Hawk\Exception\ConflictException;

/**
*
*/
class UniquenessValidator implements ValidatorInterface
{
	/**
	 * [$table description]
	 * @var string
	 */
	private $table;

	/**
	 * [$column description]
	 * @var string
	 */
	private $column;

	/**
	 * [$columnType description]
	 * @var [type]
	 */
	private $columnType;

	public function __construct($table, $column, $columnType)
	{
		$this->table      = $table;
		$this->column     = $column;
		$this->columnType = $columnType;
	}

	/**
	 * [validate description]
	 *
	 * @throws ConflictException on failure.
	 *
	 * @param  [type] $data      [description]
	 *
	 * @return [type]            [description]
	 */
	public function validate($data)
	{
		$databaseConnection = Database::connection();

		$query = $databaseConnection->createQueryBuilder()
													->select('id')
													->from("$this->table")
													->where("$this->column = :data")
													->setParameter('data', $data, $this->columnType);

		$count = count($query->execute()->fetchAll());

		if ($count !== 0)
			throw new ConflictException($this->column);
	}
}