<?php
namespace Resiliente\R4PHP\Contracts;

interface Policy
{
    /** Nome legível/para métricas */
    public function name(): string;

    /** Aplica a policy ao Executable e retorna o resultado */
    public function execute(Executable $op): mixed;
}
