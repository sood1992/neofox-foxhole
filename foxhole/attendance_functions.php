<?php
// attendance_functions.php - Complete attendance management class
class AttendanceManager {
    private $db;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("AttendanceManager: Database connection failed - " . $e->getMessage());
            $this->db = null;
        }
    }
    
    public function checkIn($employeeId, $lat, $lng, $office, $lateReason = '') {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        try {
            // Check if already checked in today
            $stmt = $this->db->prepare("
                SELECT id FROM attendance 
                WHERE employee_id = ? AND date = CURDATE()
            ");
            $stmt->execute([$employeeId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                return ['success' => false, 'message' => 'Already checked in today'];
            }
            
            // Determine status based on check-in time
            $now = new DateTime();
            $lateTime = new DateTime('10:30:00');
            $halfDayTime = new DateTime('12:01:00');
            
            $status = 'present';
            if ($now > $halfDayTime) {
                $status = 'half-day';
            } elseif ($now > $lateTime) {
                $status = 'late';
            }
            
            // Insert attendance record
            $stmt = $this->db->prepare("
                INSERT INTO attendance (
                    employee_id, check_in_time, check_in_location,
                    check_in_lat, check_in_lng, late_reason, status, date
                ) VALUES (?, NOW(), ?, ?, ?, ?, ?, CURDATE())
            ");
            
            $stmt->execute([
                $employeeId, $office, $lat, $lng, $lateReason, $status
            ]);
            
            // Update employee status to active
            $this->updateEmployeeStatus($employeeId, 'active');
            
            // Log activity
            $this->logActivity($employeeId, "Checked in from $office" . ($status == 'late' ? " (Late)" : ""));
            
            return ['success' => true, 'message' => 'Check-in successful', 'status' => $status];
            
        } catch (Exception $e) {
            error_log("Check-in error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Check-in failed: ' . $e->getMessage()];
        }
    }
    
    public function checkOut($employeeId, $lat, $lng, $office) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        try {
            // Get today's attendance record
            $stmt = $this->db->prepare("
                SELECT id, check_in_time FROM attendance 
                WHERE employee_id = ? AND date = CURDATE() AND check_in_time IS NOT NULL
            ");
            $stmt->execute([$employeeId]);
            $attendance = $stmt->fetch();
            
            if (!$attendance) {
                return ['success' => false, 'message' => 'No check-in found for today'];
            }
            
            // Update check-out time
            $stmt = $this->db->prepare("
                UPDATE attendance 
                SET check_out_time = NOW(),
                    check_out_location = ?,
                    check_out_lat = ?,
                    check_out_lng = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$office, $lat, $lng, $attendance['id']]);
            
            // Update employee status to offline
            $this->updateEmployeeStatus($employeeId, 'offline');
            
            // Calculate hours worked
            $checkIn = new DateTime($attendance['check_in_time']);
            $checkOut = new DateTime();
            $interval = $checkIn->diff($checkOut);
            $hours = $interval->h + ($interval->i / 60);
            
            $this->logActivity($employeeId, sprintf("Checked out from %s (%.1f hours)", $office, $hours));
            
            return ['success' => true, 'message' => 'Check-out successful', 'hours' => round($hours, 1)];
            
        } catch (Exception $e) {
            error_log("Check-out error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Check-out failed: ' . $e->getMessage()];
        }
    }
    
    public function autoCheckOut($employeeId) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        try {
            // Set checkout time to 6:30 PM
            $date = date('Y-m-d');
            $checkoutTime = $date . ' 18:30:00';
            
            $stmt = $this->db->prepare("
                UPDATE attendance 
                SET check_out_time = ?,
                    check_out_location = 'Auto-checkout',
                    check_out_lat = NULL,
                    check_out_lng = NULL
                WHERE employee_id = ? AND date = ? 
                AND check_in_time IS NOT NULL
                AND check_out_time IS NULL
            ");
            
            $stmt->execute([$checkoutTime, $employeeId, $date]);
            
            if ($stmt->rowCount() > 0) {
                $this->updateEmployeeStatus($employeeId, 'offline');
                $this->logActivity($employeeId, "Auto checked-out at 6:30 PM");
                return ['success' => true, 'message' => 'Auto check-out completed'];
            }
            
            return ['success' => false, 'message' => 'No active check-in found'];
            
        } catch (Exception $e) {
            error_log("Auto check-out error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Auto check-out failed'];
        }
    }
    
    public function requestLeave($employeeId, $leaveType, $fromDate, $toDate, $reason) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        try {
            // Validate dates
            $from = new DateTime($fromDate);
            $to = new DateTime($toDate);
            
            if ($from > $to) {
                return ['success' => false, 'message' => 'Invalid date range'];
            }
            
            // Check for existing leave requests in the same period
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM leave_requests 
                WHERE employee_id = ? 
                AND status != 'rejected'
                AND ((from_date <= ? AND to_date >= ?) 
                     OR (from_date <= ? AND to_date >= ?)
                     OR (from_date >= ? AND to_date <= ?))
            ");
            $stmt->execute([
                $employeeId, 
                $fromDate, $fromDate,
                $toDate, $toDate,
                $fromDate, $toDate
            ]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Leave request already exists for this period'];
            }
            
            // Insert leave request
            $stmt = $this->db->prepare("
                INSERT INTO leave_requests (
                    employee_id, leave_type, from_date, to_date, reason, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$employeeId, $leaveType, $fromDate, $toDate, $reason]);
            
            // Calculate days
            $interval = $from->diff($to);
            $days = $interval->days + 1;
            
            $this->logActivity($employeeId, "Requested $leaveType leave for $days day(s) from $fromDate to $toDate");
            
            return ['success' => true, 'message' => 'Leave request submitted successfully'];
            
        } catch (Exception $e) {
            error_log("Leave request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit leave request'];
        }
    }
    
    public function getTodayAttendance($employeeId) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM attendance 
                WHERE employee_id = ? AND date = CURDATE()
            ");
            $stmt->execute([$employeeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get attendance error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getOfficeLocations() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT * FROM office_locations WHERE is_active = 1
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get office locations error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMonthlyReport($employeeId, $month, $year) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    date,
                    check_in_time,
                    check_out_time,
                    status,
                    late_reason,
                    check_in_location,
                    check_out_location,
                    TIMEDIFF(check_out_time, check_in_time) as hours_worked
                FROM attendance 
                WHERE employee_id = ? 
                AND MONTH(date) = ? 
                AND YEAR(date) = ?
                ORDER BY date ASC
            ");
            $stmt->execute([$employeeId, $month, $year]);
            
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $stats = [
                'total_days' => count($attendance),
                'present_days' => 0,
                'late_days' => 0,
                'half_days' => 0,
                'absent_days' => 0,
                'leave_days' => 0,
                'total_hours' => 0
            ];
            
            foreach ($attendance as &$record) {
                // Calculate hours
                if ($record['check_in_time'] && $record['check_out_time']) {
                    $checkIn = new DateTime($record['check_in_time']);
                    $checkOut = new DateTime($record['check_out_time']);
                    $interval = $checkIn->diff($checkOut);
                    $hours = $interval->h + ($interval->i / 60);
                    $record['hours'] = round($hours, 1);
                    $stats['total_hours'] += $hours;
                }
                
                // Count by status
                switch ($record['status']) {
                    case 'present':
                        $stats['present_days']++;
                        break;
                    case 'late':
                        $stats['late_days']++;
                        break;
                    case 'half-day':
                        $stats['half_days']++;
                        break;
                    case 'absent':
                        $stats['absent_days']++;
                        break;
                    case 'leave':
                        $stats['leave_days']++;
                        break;
                }
            }
            
            // Calculate half days from late marks (every 3 late = 1 half day)
            $stats['calculated_half_days'] = $stats['half_days'] + floor($stats['late_days'] / 3);
            
            return [
                'records' => $attendance,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("Monthly report error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getEmployeeLeaveRequests($employeeId, $status = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "SELECT * FROM leave_requests WHERE employee_id = ?";
            $params = [$employeeId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get leave requests error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllEmployeesMonthlyReport($month, $year) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.id as employee_id,
                    e.name,
                    e.email,
                    e.role,
                    COUNT(DISTINCT a.date) as total_days,
                    COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.date END) as present_days,
                    COUNT(DISTINCT CASE WHEN a.status = 'late' THEN a.date END) as late_days,
                    COUNT(DISTINCT CASE WHEN a.status = 'half-day' THEN a.date END) as half_days,
                    COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.date END) as absent_days,
                    COUNT(DISTINCT CASE WHEN a.status = 'leave' THEN a.date END) as leave_days,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time)) / 60, 0) as total_hours
                FROM employees e
                LEFT JOIN attendance a ON e.id = a.employee_id 
                    AND MONTH(a.date) = ? 
                    AND YEAR(a.date) = ?
                WHERE e.type = 'employee'
                GROUP BY e.id
                ORDER BY e.name
            ");
            $stmt->execute([$month, $year]);
            
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate additional metrics
            foreach ($employees as &$emp) {
                $emp['calculated_half_days'] = $emp['half_days'] + floor($emp['late_days'] / 3);
                $emp['avg_hours_per_day'] = $emp['total_days'] > 0 ? 
                    round($emp['total_hours'] / $emp['total_days'], 1) : 0;
            }
            
            return $employees;
            
        } catch (Exception $e) {
            error_log("Get all employees report error: " . $e->getMessage());
            return [];
        }
    }
    
    private function updateEmployeeStatus($employeeId, $status) {
        try {
            $stmt = $this->db->prepare("
                UPDATE employees SET status = ?, last_activity = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $employeeId]);
        } catch (Exception $e) {
            error_log("Update employee status error: " . $e->getMessage());
        }
    }
    
    private function logActivity($employeeId, $activity) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (employee_id, activity, type, created_at) 
                VALUES (?, ?, 'attendance', NOW())
            ");
            $stmt->execute([$employeeId, $activity]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}
?>