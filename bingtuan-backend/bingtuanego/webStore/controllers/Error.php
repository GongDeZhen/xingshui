<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @author {&$AUTHOR&}
 */
class ErrorController extends Yaf_Controller_Abstract {

	public function errorAction($exception)
	{
		$this->respon( 0 , $exception->getMessage() );
	}


    public function respon( $success = 0 , $res  )
    {

        $result['success'] = $success;

        if( $success )
        {
            $result['data'] = $res;
        }
        else
        {
            $result['error'] = $res;
        }

        exit( json_encode( $result ) );
    }
}
