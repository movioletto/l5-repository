<?php
namespace Prettus\Repository\Generators;

/**
 * Class ControllerGenerator
 * @package Prettus\Repository\Generators
 * @author Anderson Andrade <contato@andersonandra.de>
 */
class ControllerGenerator extends Generator
{

    /**
     * Get stub name.
     *
     * @var string
     */
    protected $stub = 'controller/controller';

    /**
     * Get root namespace.
     *
     * @return string
     */
    public function getRootNamespace()
    {
        return str_replace('/', '\\', parent::getRootNamespace() . parent::getConfigGeneratorClassPath($this->getPathConfigNode()));
    }

    /**
     * Get generator path config node.
     *
     * @return string
     */
    public function getPathConfigNode()
    {
        return 'controllers';
    }

    /**
     * Get destination path for generated file.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getBasePath() . '/' . parent::getConfigGeneratorClassPath($this->getPathConfigNode(), true) . '/' . $this->getControllerName() . 'Controller.php';
    }

    /**
     * Get base path of destination file.
     *
     * @return string
     */
    public function getBasePath()
    {
        return config('repository.generator.basePath', app()->path());
    }

    /**
     * Gets controller name based on model
     *
     * @return string
     */
    public function getControllerName()
    {

        return ucfirst($this->getPluralName());
    }

    /**
     * Gets plural name based on model
     *
     * @return string
     */
    public function getPluralName()
    {

        return str_plural(lcfirst(ucwords($this->getClass())));
    }

    /**
     * Gets normal name based on model
     *
     * @return string
     */
    public function getNormalName()
    {

        return lcfirst(ucwords($this->getClass()));
    }

    /**
     * Get array replacements.
     *
     * @return array
     */
    public function getReplacements()
    {
    	$pluralName = $this->getNormalName();
    	$singularName = $this->getNormalName();

	    if(config('repository.generator.plural', true)){
		    $pluralName = $this->getPluralName();
	    }

	    if(config('repository.generator.singular', true)){
		    $singularName = $this->getSingularName();
	    }

        return array_merge(parent::getReplacements(), [
            'controller' => $this->getControllerName(),
            'plural'     => $pluralName,
            'singular'   => $singularName,
            'validator'  => $this->getValidator(),
            'repository' => $this->getRepository(),
            'appname'    => $this->getAppNamespace(),
        ]);
    }

    /**
     * Gets singular name based on model
     *
     * @return string
     */
    public function getSingularName()
    {
        return str_singular(lcfirst(ucwords($this->getClass())));
    }

    /**
     * Gets validator full class name
     *
     * @return string
     */
    public function getValidator()
    {
        $validatorGenerator = new ValidatorGenerator([
            'name' => $this->name,
        ]);

        $validator = $validatorGenerator->getRootNamespace() . '\\' . $validatorGenerator->getName();

        return 'use ' . str_replace([
            "\\",
            '/'
        ], '\\', $validator) . 'Validator;';
    }


    /**
     * Gets repository full class name
     *
     * @return string
     */
    public function getRepository()
    {
        $repositoryGenerator = new RepositoryInterfaceGenerator([
            'name' => $this->name,
        ]);

        $repository = $repositoryGenerator->getRootNamespace() . '\\' . $repositoryGenerator->getName();

        return 'use ' . str_replace([
            "\\",
            '/'
        ], '\\', $repository) . 'Repository;';
    }
}
