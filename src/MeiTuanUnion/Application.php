<?php
/**
 * 2021年01月02日 23:32
 */

namespace OneCoder\EasyTaoKe\MeiTuanUnion;

use Mockery\Exception;
use OneCoder\EasyTaoKe\Base\Application as BaseApplication;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;

/***
 * 美团联盟应用
 * Class Application
 *
 * @package OneCoder\EasyTaoKe\MeiTuanUnion
 */
class Application extends BaseApplication
{
    const SIGNATURE_PARAM_NAME = 'sign';  //美团回调参数签名字段名称

    const TUAN_GOU_ORDER = 0; //团购订单
    const JIU_DIAN_ORDER = 2; //酒店订单
    const WAIMAI_ORDER   = 4; //外卖订单
    const HUA_FEI_ORDER  = 5; //花费订单
    const SHAN_GOU_ORDER = 6; //闪购订单

    const WAIMAI_ACTIVITY_ID   = 2; //外卖活动ID
    const SHAN_GOU_ACTIVITY_ID = 4; //闪购活动ID

    const H5_LINK_TYPE     = 1; //H5类型的链接
    const DEEP_LINK_TYPE   = 2; //DEEP类型的链接
    const CENTER_LINK_TYPE = 3; //中间唤起页的链接
    const WECHAT_LINK_TYPE = 4; //微信小程序Path

    static $LINK_TYPE_ARR = [
        self::H5_LINK_TYPE     => 'h5_link',
        self::DEEP_LINK_TYPE   => 'deep_link',
        self::CENTER_LINK_TYPE => 'center_link',
        self::WECHAT_LINK_TYPE => 'wechat_path',
    ];

    private $callbackSecret = ''; //回调secret，美团联盟后台获取
    private $secret         = ''; //应用secret，美团联盟后台获取
    private $key            = ''; //应用key,美团联盟后台获取

    protected $apiUrl       = 'https://runion.meituan.com/api/';
    protected $getUrlAPiUrl = 'https://runion.meituan.com/generateLink';

    public function __construct($config = [])
    {
        if (!empty($config['key'])) {
            $this->key = $config['key'];
        }
        if (!empty($config['secret'])) {
            $this->secret = $config['secret'];
        }
        if (!empty($config['callback_secret'])) {
            $this->callbackSecret = $config['callback_secret'];
        }
    }

    /***
     * 生成签名算法
     *
     * @param      $params     参数
     * @param bool $isCallback , true回调验证使用，false接口验证
     *
     * @return string 验签生成的字符串
     */
    protected function generateSign($params, $isCallback = false)
    {
        $secret = $this->secret;
        if ($isCallback) {
            $secret = $this->callbackSecret;
        }
        unset($params["sign"]);
        ksort($params);
        $str = $secret; // $secret为分配的密钥
        foreach ($params as $key => $value) {
            $str .= $key . $value;
        }
        $str  .= $secret;
        $sign = md5($str);
        return $sign;
    }

    /***
     * 验证回调结果
     * @param $params 美团接口回调的所有参数
     *
     * @return bool
     */
    public function validateCallback($params)
    {
        $sign = $params[self::SIGNATURE_PARAM_NAME];
        return $this->generateSign($params) === $sign;
    }

    /***
     * 生成推广URL
     *
     * @param       $sid       美团sid
     * @param int   $actId     美团活动ID
     * @param array $linkTypes 要获取的美团链接类型，空代表全部四种
     *
     * @return array
     */
    public function generateUrl($sid, $actId = self::WAIMAI_ORDER, $linkTypes = [])
    {
        $url         = $this->getUrlAPiUrl;
        $params      = [
            'key'   => $this->key,
            'actId' => (string)$actId,
            'sid'   => $sid,
        ];
        $linkTypeArr = self::$LINK_TYPE_ARR;
        if (!$linkTypes) {
            $endLinkTypes = array_keys($linkTypeArr);
        } else if (is_array($linkTypes)) {
            $endLinkTypes = $linkTypes;
        } else {
            $endLinkTypes = [$linkTypes];
        }
        $urls = [];
        foreach ($endLinkTypes as $linkType) {
            $params['linkType'] = (int)$linkType;
            $params['sign']     = $this->generateSign($params);
            $result             = $this->getData($url, $params);
            if (!empty($result['data'])) {
                $urls[$linkTypeArr[$linkType]] = $result['data'];
            }
        }
        return $urls;
    }

    /***
     * 新订单列表查询
     *
     * @param string $startTime     订单开始时间
     * @param string $endTime       订单结束时间
     * @param int    $type          查询订单类型
     * @param int    $page          页码
     * @param int    $pageSize      分页size
     * @param int    $queryTimeType 查询时间类型
     *
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     */
    function getOrderList($startTime = '', $endTime = '', $type = self::WAIMAI_ORDER, $page = 1, $pageSize = 10, $queryTimeType = 1)
    {
        $action         = 'orderList';
        $url            = $this->apiUrl . $action;
        $startTime      = strtotime($startTime);
        $endTime        = strtotime($endTime);
        $params         = [
            'key'           => $this->key,
            'ts'            => (string)time(),
            'startTime'     => (string)$startTime,
            'endTime'       => (string)$endTime,
            'queryTimeType' => (string)$queryTimeType,
            'page'          => (string)$page,
            'limit'         => (string)$pageSize,
            'type'          => (string)$type,
        ];
        $params['sign'] = $this->generateSign($params);
        $result         = $this->getData($url, $params);
        return $result;
    }

    /**
     * @param string $startTime 领取开始时间
     * @param string $endTime   领取结束时间
     * @param int    $type      券用于业务类型
     * @param int    $page      页码
     * @param int    $pageSize  分页size
     *
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     */
    function getCouponList($startTime = '', $endTime = '', $type = self::WAIMAI_ORDER, $page = 1, $pageSize = 10)
    {
        $action         = 'couponList';
        $url            = $this->apiUrl . $action;
        $startTime      = strtotime($startTime);
        $endTime        = strtotime($endTime);
        $params         = [
            'key'       => $this->key,
            'ts'        => (string)time(),
            'startTime' => (string)$startTime,
            'endTime'   => (string)$endTime,
            'page'      => (string)$page,
            'limit'     => (string)$pageSize,
            'type'      => (string)$type,
        ];
        $params['sign'] = $this->generateSign($params);
        $result         = $this->getData($url, $params);
        return $result;
    }

    /***
     * 旧的订单接口
     *
     * @param string $orderId 为空时是旧的订单列表查询
     * @param int    $type    查询订单类型
     * @param int    $full    是否返回完整订单信息(即是否包含返佣、退款信息)
     *
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     */
    function getOrdersDetail($orderId = '', $type = self::WAIMAI_ORDER, $full = 0)
    {
        $action = 'rtnotify';
        $url    = $this->apiUrl . $action;
        $params = [
            'key'  => $this->key,
            'type' => (string)$type,
        ];
        if ($orderId) {
            $params['oid'] = $orderId;
        }
        if ($full) {
            $params['full'] = 1;
        }
        $params['sign'] = $this->generateSign($params);
        $result         = $this->getData($url, $params);
        return $result;
    }

    /***
     * 获取数据
     *
     * @param $action
     * @param $params
     *
     * @return bool|mixed|\Psr\Http\Message\StreamInterface|string
     */
    protected function getData($url, $params)
    {
        try {
            $client   = new Client(['http_errors' => false]);
            $response = $client->request('get', $url, [
                'query' => $params
            ]);
            $body     = $response->getBody();

            if ($body instanceof Stream) {
                $body = $body->getContents();
            }
            $res = json_decode($body, true);
            if ($res) {
                return $res;
            }
            return $body;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getMessage();
        }
    }
}
