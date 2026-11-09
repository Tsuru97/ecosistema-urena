<?php

namespace App\Ai\Tools;

interface AiToolInterface
{
    /**
     * Retorna el nombre único identificador de la herramienta para la IA.
     */
    public function getName(): string;

    /**
     * Retorna la descripción textual de qué hace y cuándo debe usarse.
     */
    public function getDescription(): string;

    /**
     * Retorna un conjunto de palabras clave asociadas para búsquedas y enrutamiento.
     */
    public function getKeywords(): array;

    /**
     * Retorna el esquema JSON Schema de parámetros que acepta la herramienta.
     */
    public function getParametersSchema(): array;

    /**
     * Ejecuta la lógica comercial de la herramienta (consultas SQL/Eloquent, etc.).
     */
    public function execute(array $arguments);
}
