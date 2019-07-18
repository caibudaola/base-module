<?php
namespace Module\DataApi;

use Module\Base\Http;

class DataApi  extends Http {

	public function __construct($config)
	{
        $this->debug = isset($config['debug']) ? $config['debug'] : 'false';
        $this->base_url = $config['base_url'];
	}

    /**
     * 获取用户报表信息
     *
     * @param null $nStartTime
     * @param null $nEndTime
     * @param null $userIdOrName
     * @param null $referrer_id
     * @param null $b_ReturnFlag
     * @return null
     */
    public function userReportList($nStartTime=null, $nEndTime=null, $userIdOrName=null, $referrer_id=null, $b_ReturnFlag=null)
    {
        $data = [    ];
        if ($nStartTime) {
            $data['start_date'] = $nStartTime;
        }
        if ($nEndTime) {
            $data['end_date'] = $nEndTime;
        }
        if ($referrer_id) {
            $data['referrer_id'] = $referrer_id;
        }
        if ($b_ReturnFlag) {
            $data['b_ReturnFlag'] = $b_ReturnFlag;
        }
        if ($userIdOrName) {
            $data['user_id_or_name'] = $userIdOrName;
        }

        return $this->httpGet('api/reports/user-report-list', $data);
    }
}
