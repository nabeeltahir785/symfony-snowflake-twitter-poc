<?php

namespace App\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for entities that use Snowflake IDs
 *
 * This trait provides the ID property and getter/setter methods for
 * entities that use Snowflake IDs, reducing code duplication.
 */
trait SnowflakeIdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="snowflake")
     */
    private ?string $id = null;

    /**
     * Get entity ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set entity ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }
}