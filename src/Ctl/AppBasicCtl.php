<?php
namespace Gn\Ctl;

use ErrorException;
use InvalidArgumentException;

// from Slim
use Slim\Container;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;

/**
 * Api basic controller functions for extending.
 *
 * @author Nick Feng
 * @since 1.0
 */
abstract class AppBasicCtl extends BasicCtl
{
    /**
     * Variable name for *.phtml
     * 
     * @var string
     */
    protected string $argsOutputName;

    /**
     * File extension name for renderer.
     * 
     * @var string
     */
    protected string $templateExtension;

    /**
     * Renderer object.
     * 
     * @var PhpRenderer
     */
    protected PhpRenderer $renderer;

    /**
     * Constructor.
     *
     * @param Container $container
     * @param string $argsOutputName
     * @param string $templateExtension
     * @throws ErrorException
     */
    public function __construct( 
        Container $container,
        string $argsOutputName = 'template_var',
        string $templateExtension = '.phtml'
    ) {
        $renderer = $container->get('renderer');
        if ( !( $renderer instanceof PhpRenderer ) ) {
            throw new InvalidArgumentException('Expected instance of renderer.');
        } else if ( empty($renderer)) {
            throw new ErrorException('The argument "renderer" cannot be empty.');
        } else if (empty($argsOutputName)) {
            throw new ErrorException('The argument "argsOutputName" cannot be empty.');
        } else if (empty($templateExtension)) {
            throw new ErrorException('The argument "templateExtension" cannot be empty.');
        }
        parent::__construct($container);
        $this->renderer = $renderer;
        $this->argsOutputName = $argsOutputName;
        $this->templateExtension = $templateExtension;
    }

    /**
     * Render a template with customer arguments.
     *
     * @param Response $resp
     * @param array $args
     * @param array|boolean $user user data.
     * @param string|null $template
     * @return Response|bool
     * @author Nick Feng
     */
    protected function appRenderer ( 
        Response $resp, 
        array $args, 
        $user, 
        ?string $template = NULL
    ) {
        if ( is_array( $user ) && !empty( $user ) ) {
            $args[ $this->argsOutputName ] = [
                'app'  => $this->container->get('settings')['app'],
                'user' => $user
            ];
            $template = $template ?: '404';
            return $this->renderer->render( $resp, ( $template . $this->templateExtension ), $args );
        }
        return false;
    }
}