<?php
namespace marshung\helper;

/**
 * Array Helper for PHP code
 * 
 * @author Mars Hung <tfaredxj@gmail.com>
 */
class ArrayHelper
{
    
    /**
     * *********************************************
     * ************** Public Function **************
     * *********************************************
     */
    
    /**
     * Index by keys
     *
     * @param mixed $data Array/stdClass data for handling
     * @param mixed $keys keys for index key (Array/string)
     * @param boolean $obj2array stdClass convert to array
     * @return mixed Result with indexBy Keys
     */
    public static function indexBy(& $data, $keys, $obj2array = false)
    {
        // Refactor Array $data structure by $keys
        return self::_refactorBy($data, $keys, $obj2array, $type = 'indexBy');
    }
    
    /**
     * Group by keys
     *
     * @param array|stdClass $data Array/stdClass data for handling
     * @param string|array $keys
     * @param boolean $obj2array Array content convert to array (when object)
     */
    public static function groupBy(& $data, $keys, $obj2array = false)
    {
        // Refactor Array $data structure by $keys
        return self::_refactorBy($data, $keys, $obj2array, $type = 'groupBy');
    }
    
    /**
     * Index Only by keys, No Data
     *
     * @param array|stdClass $data Array/stdClass data for handling
     * @param string|array $keys
     * @param boolean $obj2array Array content convert to array (when object)
     */
    public static function indexOnly(& $data, $keys, $obj2array = false)
    {
        // Refactor Array $data structure by $keys
        return self::_refactorBy($data, $keys, $obj2array, $type = 'indexOnly');
    }
    
    /**
     * Get Data content by index
     * 
     * Usage:
     * - $data = ['user' => ['name' => 'Mars', 'birthday' => '2000-01-01']];
     * - var_export(getContent($data)); // full $data content
     * - var_export(getContent($data, 'user')); // ['name' => 'Mars', 'birthday' => '2000-01-01']
     * - echo getContent($data, ['user', 'name']); // Mars
     * 
     * @param array $data
     * @param array|string $indexTo Content index of the data you want to get
     * @param bool $exception default false
     * @throws \Exception
     * @return array
     */
    public static function getContent(Array $data, $indexTo = [], $exception = false)
    {
        //* Arguments prepare */
        $indexTo = (array)$indexTo;
        $indexed = [];
        
        foreach ($indexTo as $idx) {
            // save runed index
            $indexed[] = $idx;
            
            if (isset($data[$idx])) {
                // If exists, Get values by recursion
                $data = $data[$idx];
            } else {
                // Not exists, Exception or return []
                if ($exception) {
                    throw new \Exception('Error index: ' . implode(' => ', $indexed), 400);
                } else {
                    $data = [];
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 從目標資料中的指定多欄位搜集資料，並組成陣列清單
     * 
     * 一般狀況，使用array_column()內建函式可完成資料搜集，但如需搜集多欄位資料則無法使用array_column()
     * 
     * 資料陣列，格式：array(stdClass|array usersInfo1, stdClass|array usersInfo2, stdClass|array usersInfo3, ............);
     * 使用範例：
     * - $data = $this->db->select('*')->from('users')->get()->result();
     * - 欄位 manager, sign_manager, create_user 值放在同一個一維陣列中
     * - $ssnList1 = \marshung\helper\ArrayHelper::gather($data, array('manager', 'sign_manager','create_user'), 1);
     * - 欄位 manager 值放一個陣列, 欄位 sign_manager, create_user 值放同一陣列中，形成2維陣列 $dataList2 = ['manager' => [], 'other' => []];
     * - $ssnList2 = \marshung\helper\ArrayHelper::gather($data, array('manager' => array('manager'), 'other' => array('sign_manager','create_user')), 1);
     *
     * 遞迴效率太差 - 改成遞迴到最後一層陣列後直接處理，不再往下遞迴
     *
     * @author Mars.Hung <tfaredxj@gmail.com>
     *
     * @param array $data
     *            資料陣列
     * @param array $colNameList
     *            資料陣列中，目標資料的Key名稱
     * @param number $objLv
     *            資料物件所在層數
     * @param array $dataList
     *            遞迴用
     */
    public static function gather($data, $colNameList, $objLv = 1, $dataList = array())
    {
        // 將物件轉成陣列
        $data = is_object($data) ? (array)$data : $data;
        
        // 遍歷陣列 - 只處理陣列
        if (is_array($data) && ! empty($data)) {
            if ($objLv > 1) {
                // === 超過1層 ===
                foreach ($data as $k => $row) {
                    // 遞迴處理
                    $dataList = self::gather($row, $colNameList, $objLv - 1, $dataList);
                }
            } else {
                // === 1層 ===
                // 遍歷要處理的資料
                foreach ($data as $k => $row) {
                    $row = (array) $row;
                    // 遍歷目標欄位名稱
                    foreach ($colNameList as $tKey1 => $tCol) {
                        if (is_array($tCol)) {
                            // === 如果目標是二維陣列，輸出的資料也要依目標陣列的第一維度分類 ===
                            foreach ($tCol as $tKey2 => $tCol2) {
                                if (isset($row[$tCol2])) {
                                    $dataList[$tKey1][$row[$tCol2]] = $row[$tCol2];
                                }
                            }
                        } else {
                            // === 目標是一維陣列，不需分類 ===
                            if (isset($row[$tCol])) {
                                $dataList[$row[$tCol]] = $row[$tCol];
                            }
                        }
                    }
                }
            }
        }
        
        return $dataList;
    }
    
    
    
    
    /**
     * **********************************************
     * ************** Private Function **************
     * **********************************************
     */
    
    /**
    * Refactor Array $data structure by $keys
    *
    * @param array|stdClass $data Array/stdClass data for handling
    * @param string|array $keys
    * @param boolean $obj2array Array content convert to array (when object)
    * @param string $type indexBy(index)/groupBy(group)/only index,no data(indexOnly/noData)
    */
    protected static function _refactorBy(& $data, $keys, $obj2array = false, $type = 'index')
    {
        // 參數處理
        $keys = (array)$keys;
        
        $result = [];
        
        // 遍歷待處理陣列
        foreach ($data as $row) {
            // 旗標，是否取得索引
            $getIndex = false;
            // 位置初炲化 - 傳址
            $rRefer = & $result;
            
            // 遍歷$keys陣列 - 建構索引位置
            foreach ($keys as $key) {
                $vKey = null;
                
                // 取得索引資料 - 從$key
                if (is_object($row) && isset($row->{$key})) {
                    $vKey = $row->{$key};
                } elseif (is_array($row) && isset($row[$key])) {
                    $vKey = $row[$key];
                }
                
                // 有無法取得索引資料，跳出
                if (is_null($vKey)) {
                    $getIndex = false;
                    break;
                }
                
                // 變更位置 - 傳址
                $rRefer = & $rRefer[$vKey];
                
                // 本次索引完成
                $getIndex = true;
            }
            
            // 略過無法取得索引資料
            if (! $getIndex) {
                continue;
            }
            
            // 將資料寫入索引位置
            switch ($type) {
                case 'index':
                case 'indexBy':
                default:
                    $rRefer = $obj2array ? (array)$row : $row;
                    break;
                case 'group':
                case 'groupBy':
                    $rRefer[] = $obj2array ? (array)$row : $row;
                    break;
                case 'indexOnly':
                case 'noData':
                    $rRefer = '';
                    break;
            }
        }
        
        return $data = $result;
    }
}