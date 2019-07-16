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
     * @param null $strIdOrUsername
     * @param null $nStartTime
     * @param null $nEndTime
     * @return mixed
     */
    public function userReportList($strIdOrUsername=null, $nStartTime=null, $nEndTime=null)
    {
        $data = [    ];
        if ($strIdOrUsername) {
            $data['user_id_or_name'] = $strIdOrUsername;
        }
        if ($nStartTime) {
            $data['start_date'] = $nStartTime;
        }
        if ($nEndTime) {
            $data['end_date'] = $nEndTime;
        }
        return $this->httpGet('api/reports/user-report-list', $data);
    }
}
