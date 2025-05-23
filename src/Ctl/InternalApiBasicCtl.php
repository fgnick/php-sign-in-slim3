<?php
namespace Gn\Ctl;

use ErrorException;
use Gn\Interfaces\BaseRespCodesInterface;

// from Slim
use Slim\Container;
use Slim\Http\Request;

/**
 * This class works for extending to API of internal API passing.
 * 
 * @author Nick Feng
 * @since 1.0
 */
abstract class InternalApiBasicCtl extends BasicCtl implements BaseRespCodesInterface
{
    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        // 防呆，免得誤用到不是 internal API 的 channel 之中
        if ( empty( $container->jwt ) || !is_object( $container->jwt ) ) {
            throw new ErrorException( 'Authorization is not existed in header' );
        } else if ( !isset( $container->jwt->data->channel ) ) {
            // internal API 不一定都是用同一個 channel 做辨識，所以交給他的 child 去個別需求判斷
            throw new ErrorException( 'Empty channel: jwt=' . parent::jsonLogStr( $container->jwt ) );
        }
        // 這邊不如 ApiBasicCtl.php。所以，不會有 memData。因此，不需要檢查這個。
        // 而其他的 token 檢查，都已經盡可能的在 middleware.php 之中完成了
        parent::__construct( $container );
    }

    /**
     * 確認發行公司名稱，以及該公司被允許的 routing 路徑
     *
     * @param Request $request
     * @return bool
     */
    public function accessPath ( Request $request ): bool
    {
        $settings = $this->container->get( 'settings' );
        $uri = $request->getUri();
        
        // Base Path
        //If your Slim application's front-controller lives in a physical subdirectory beneath your document root directory, 
        // you can fetch the HTTP request's physical base path (relative to the document root) with the Uri object's getBasePath() method. 
        // This will be an empty string if the Slim application is installed in the document root's top-most directory.

        $basePath = $uri->getBasePath();
        $path = $uri->getPath();
        $outsource_name = $this->container->jwt->iss;
        $path = $basePath . '/' . ltrim( $path, '/' );
        return in_array( $path, $settings['oauth']['internal_access'][$outsource_name]['path'] );
    }
}