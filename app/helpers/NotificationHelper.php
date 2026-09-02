<?php
namespace App\Helpers;

use App\Models\NotificationModel;

class NotificationHelper {

    public static function create($type, $title, $message, $targetDepartment, $targetUrl = null, $createdBy = null) {
        try {
            return NotificationModel::create($title, $message, $type, $targetDepartment, $targetUrl, $createdBy);
        } catch (\Exception $e) {
            error_log('NotificationHelper::create error: ' . $e->getMessage());
            return false;
        }
    }

    public static function poCreated($poLabel, $customerName, $poId, $createdBy) {
        $url = '?controller=warehouse&action=purchaseOrders';
        self::create('po', 'New Purchase Order', $customerName . ' — ' . $poLabel . ' has been created.', 'warehouse', $url, $createdBy);
        self::create('po', 'New Purchase Order', $customerName . ' — ' . $poLabel . ' has been created.', 'production', $url, $createdBy);
        self::create('po', 'New Purchase Order', $customerName . ' — ' . $poLabel . ' has been created.', 'admin', $url, $createdBy);
    }

    public static function deliveryCreated($poLabel, $drNumber, $quantity, $createdBy) {
        $url = '?controller=warehouse&action=deliveries';
        self::create('delivery', 'Delivery Recorded', 'DR ' . $drNumber . ' — ' . $poLabel . ' (' . $quantity . ' pcs) has been delivered.', 'warehouse', $url, $createdBy);
        self::create('delivery', 'Delivery Recorded', 'DR ' . $drNumber . ' — ' . $poLabel . ' (' . $quantity . ' pcs) has been delivered.', 'finance', '?controller=finance&action=deliveries', $createdBy);
        self::create('delivery', 'Delivery Recorded', 'DR ' . $drNumber . ' — ' . $poLabel . ' (' . $quantity . ' pcs) has been delivered.', 'admin', $url, $createdBy);
    }

    public static function deliveryReported($deliveryId, $drNumber, $reason, $reportedBy) {
        $url = '?controller=admin&action=delivered';
        self::create('delivery', 'Delivery Issue Reported', 'DR ' . $drNumber . ' — Reason: ' . $reason, 'admin', $url, $reportedBy);
        self::create('delivery', 'Delivery Issue Reported', 'DR ' . $drNumber . ' — Reason: ' . $reason, 'warehouse', '?controller=warehouse&action=deliveries', $reportedBy);
    }

    public static function deliveryReportResolved($reportId, $resolvedBy) {
        $url = '?controller=admin&action=delivered';
        self::create('delivery', 'Delivery Report Resolved', 'Delivery report #' . $reportId . ' has been resolved.', 'warehouse', $url, $resolvedBy);
    }

    public static function productionUpdated($poLabel, $addedQty, $lotNumber, $stsRef, $updatedBy) {
        $url = '?controller=warehouse&action=purchaseOrders';
        $lotText = $lotNumber ? ' (Lot: ' . $lotNumber . ')' : '';
        self::create('production', 'Production Updated', $poLabel . ' — +' . $addedQty . ' pcs' . $lotText . ' [' . $stsRef . ']', 'warehouse', $url, $updatedBy);
        self::create('production', 'Production Updated', $poLabel . ' — +' . $addedQty . ' pcs' . $lotText . ' [' . $stsRef . ']', 'admin', $url, $updatedBy);
    }

    public static function productionReported($historyId, $reason, $reportedBy) {
        $url = '?controller=admin&action=productionHistory';
        self::create('production', 'Production Report Submitted', 'Production history #' . $historyId . ' — Reason: ' . $reason, 'admin', $url, $reportedBy);
    }

    public static function siNumberNeeded($deliveryId, $poLabel, $createdBy) {
        $url = '?controller=finance&action=viewDelivery&id=' . $deliveryId;
        self::create('finance', 'Sales Invoice Needed', 'DR for ' . $poLabel . ' is missing an SI number.', 'finance', $url, $createdBy);
    }

    public static function qcInspectionNeeded($poLabel, $lotNumber, $createdBy) {
        $url = '?controller=qc';
        self::create('qc', 'QC Inspection Needed', $poLabel . ' — Lot ' . $lotNumber . ' is ready for inspection.', 'qc', $url, $createdBy);
    }

    public static function backloadCreated($drNumber, $poLabel, $quantity, $createdBy) {
        $url = '?controller=warehouse&action=viewBackloads';
        self::create('backload', 'Backload Recorded', 'DR ' . $drNumber . ' — ' . $poLabel . ' (' . $quantity . ' pcs returned).', 'warehouse', $url, $createdBy);
        self::create('backload', 'Backload Recorded', 'DR ' . $drNumber . ' — ' . $poLabel . ' (' . $quantity . ' pcs returned).', 'admin', '?controller=admin&action=viewBackloads', $createdBy);
    }
}
