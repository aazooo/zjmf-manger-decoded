<?php

namespace think\api\concerns;

use think\api\request\DefaultRequests;
use think\api\request\douyin\DouyinRequests;

/**
 * @method DouyinRequests douyin()
 */
trait InteractsWithRequest
{
    use DefaultRequests;
}
