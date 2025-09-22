<?php
namespace Resiliente\R4PHP\Contracts;

interface Executable
{
    /**
     * Executa a operação protegida.
     * Deve lançar exceções em caso de erro (para Retry/CB capturarem).
     */
    public function __invoke(): mixed;
}
