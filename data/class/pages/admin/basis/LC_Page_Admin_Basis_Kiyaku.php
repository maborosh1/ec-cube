<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2010 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// {{{ requires
require_once(CLASS_REALDIR . "pages/admin/LC_Page_Admin.php");

/**
 * 会員規約設定 のページクラス.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 * @version $Id$
 */
class LC_Page_Admin_Basis_Kiyaku extends LC_Page_Admin {

    // }}}
    // {{{ functions

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $this->tpl_mainpage = 'basis/kiyaku.tpl';
        $this->tpl_subnavi = 'basis/subnavi.tpl';
        $this->tpl_subno = 'kiyaku';
        $this->tpl_subtitle = '会員規約登録';
        $this->tpl_mainno = 'basis';
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action() {
        $objSess = new SC_Session();
        $objDb = new SC_Helper_DB_Ex();

        // 認証可否の判定
        SC_Utils_Ex::sfIsSuccess($objSess);

        $mode = $this->getMode();

        if (!empty($_POST)) {
            $this->arrErr = $this->lfCheckError($mode, $_POST);
            if (!empty($this->arrErr['kiyaku_id'])) {
                SC_Utils_Ex::sfDispException();
                return;
            }
        }

        // 要求判定
        switch($mode) {
        // 編集処理
        case 'edit':
            // POST値の引き継ぎ
            $this->arrForm = $_POST;

            if(count($this->arrErr) <= 0) {
                if($_POST['kiyaku_id'] == "") {
                    $this->lfInsertClass($this->arrForm, $_SESSION['member_id']);    // 新規作成
                } else {
                    $this->lfUpdateClass($this->arrForm, $_POST['kiyaku_id']);    // 既存編集
                }
                // 再表示
                $this->objDisplay->reload();
            } else {
                // POSTデータを引き継ぐ
                $this->tpl_kiyaku_id = $_POST['kiyaku_id'];
            }
            break;
        // 削除
        case 'delete':
            $objDb->sfDeleteRankRecord("dtb_kiyaku", "kiyaku_id", $_POST['kiyaku_id'], "", true);
            // 再表示
            $this->objDisplay->reload();
            break;
        // 編集前処理
        case 'pre_edit':
            // 編集項目を取得する。
            $arrKiyakuData = $this->lfGetKiyakuDataByKiyakuID($_POST['kiyaku_id']);

            // 入力項目にカテゴリ名を入力する。
            $this->arrForm['kiyaku_title'] = $arrKiyakuData[0]['kiyaku_title'];
            $this->arrForm['kiyaku_text'] = $arrKiyakuData[0]['kiyaku_text'];
            // POSTデータを引き継ぐ
            $this->tpl_kiyaku_id = $_POST['kiyaku_id'];
        break;
        case 'down':
            $objDb->sfRankDown("dtb_kiyaku", "kiyaku_id", $_POST['kiyaku_id']);
            // 再表示
            $this->objDisplay->reload();
            break;
        case 'up':
            $objDb->sfRankUp("dtb_kiyaku", "kiyaku_id", $_POST['kiyaku_id']);
            // 再表示
            $this->objDisplay->reload();
            break;
        default:
            break;
        }

        $this->arrKiyaku = $this->lfGetKiyakuList();
    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }

    /* DBへの挿入 */
    function lfInsertClass($arrData, $member_id) {
        $objQuery =& SC_Query::getSingletonInstance();
        // INSERTする値を作成する。
        $sqlval['kiyaku_title'] = $arrData['kiyaku_title'];
        $sqlval['kiyaku_text'] = $arrData['kiyaku_text'];
        $sqlval['creator_id'] = $member_id;
        $sqlval['rank'] = $objQuery->max("rank", "dtb_kiyaku") + 1;
        $sqlval['update_date'] = "Now()";
        $sqlval['create_date'] = "Now()";
        // INSERTの実行
        $sqlval['kiyaku_id'] = $objQuery->nextVal('dtb_kiyaku_kiyaku_id');
        $ret = $objQuery->insert("dtb_kiyaku", $sqlval);
        return $ret;
    }

    function lfGetKiyakuDataByKiyakuID($kiyaku_id) {
        $objQuery =& SC_Query::getSingletonInstance();

        $where = "kiyaku_id = ?";
        return $objQuery->select("kiyaku_text, kiyaku_title", "dtb_kiyaku", $where, array($kiyaku_id));
    }

    function lfGetKiyakuList() {
        $objQuery =& SC_Query::getSingletonInstance();

        $where = "del_flg <> 1";
        $objQuery->setOrder("rank DESC");
        return $objQuery->select("kiyaku_title, kiyaku_text, kiyaku_id", "dtb_kiyaku", $where);
    }

    /* DBへの更新 */
    function lfUpdateClass($arrData, $kiyaku_id) {
        $objQuery =& SC_Query::getSingletonInstance();
        // UPDATEする値を作成する。
        $sqlval['kiyaku_title'] = $arrData['kiyaku_title'];
        $sqlval['kiyaku_text'] = $arrData['kiyaku_text'];
        $sqlval['update_date'] = "Now()";
        $where = "kiyaku_id = ?";
        // UPDATEの実行
        $ret = $objQuery->update("dtb_kiyaku", $sqlval, $where, array($kiyaku_id));
        return $ret;
    }

    /* 取得文字列の変換 */
    function lfConvertParam($array) {
        // 文字変換
        $arrConvList['kiyaku_title'] = "KVa";
        $arrConvList['kiyaku_text'] = "KVa";

        foreach ($arrConvList as $key => $val) {
            // POSTされてきた値のみ変換する。
            if(isset($array[$key])) {
                $array[$key] = mb_convert_kana($array[$key] ,$val);
            }
        }
        return $array;
    }

    /**
     * 入力エラーチェック
     *
     * @param string $mode
     * @return array
     */
    function lfCheckError($mode, $post) {
        $arrErr = array();

        switch ($mode) {
            case 'edit':
                $_POST = $this->lfConvertParam($post);

                $objErr = new SC_CheckError();
                $objErr->doFunc(array("規約タイトル", "kiyaku_title", SMTEXT_LEN), array("EXIST_CHECK","SPTAB_CHECK","MAX_LENGTH_CHECK"));
                $objErr->doFunc(array("規約内容", "kiyaku_text", MLTEXT_LEN), array("EXIST_CHECK","SPTAB_CHECK","MAX_LENGTH_CHECK"));
                if(!isset($objErr->arrErr['name'])) {
                    $objQuery =& SC_Query::getSingletonInstance();
                    $arrRet = $objQuery->select("kiyaku_id, kiyaku_title", "dtb_kiyaku", "del_flg = 0 AND kiyaku_title = ?", array($post['kiyaku_title']));
                    // 編集中のレコード以外に同じ名称が存在する場合
                    if ($arrRet[0]['kiyaku_id'] != $post['kiyaku_id'] && $arrRet[0]['kiyaku_title'] == $post['kiyaku_title']) {
                        $objErr->arrErr['name'] = "※ 既に同じ内容の登録が存在します。<br>";
                    }
                }
            case 'delete':
            case 'pre_edit':
            case 'down':
            case 'up':
                $this->objFormParam = new SC_FormParam();
                $this->objFormParam->addParam('規約ID', 'kiyaku_id', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
                $this->objFormParam->setParam($post);
                $this->objFormParam->convParam();
                $arrErr = $this->objFormParam->checkError();

                break;
            default:
                break;
        }
        return array_merge((array)$objErr->arrErr, (array)$arrErr);
    }
}
?>
