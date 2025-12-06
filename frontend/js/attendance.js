/**
 * DCROP System - Attendance JavaScript
 * File: frontend/js/attendance.js
 * Functions for attendance submission and management
 */

/**
 * Get user's current GPS location
 * @returns {Promise<{latitude: number, longitude: number, accuracy: number}>}
 */
function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported by your browser'));
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                });
            },
            (error) => {
                let errorMessage = 'Unable to retrieve location. ';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please enable location permissions in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable. Please try again.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Location request timed out. Please try again.';
                        break;
                    default:
                        errorMessage += 'An unknown error occurred.';
                }
                
                reject(new Error(errorMessage));
            },
            options
        );
    });
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 * @param {number} lat1 - Latitude of point 1
 * @param {number} lon1 - Longitude of point 1
 * @param {number} lat2 - Latitude of point 2
 * @param {number} lon2 - Longitude of point 2
 * @returns {number} Distance in kilometers
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in kilometers
    const dLat = toRadians(lat2 - lat1);
    const dLon = toRadians(lon2 - lon1);
    
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    
    return distance;
}

/**
 * Convert degrees to radians
 * @param {number} degrees
 * @returns {number}
 */
function toRadians(degrees) {
    return degrees * (Math.PI / 180);
}

/**
 * Verify if location is within community boundaries
 * @param {number} userLat - User's latitude
 * @param {number} userLon - User's longitude
 * @param {string} community - Community name
 * @returns {object} {isValid: boolean, distance: number, message: string}
 */
function verifyLocationInCommunity(userLat, userLon, community) {
    // Community center coordinates (example data)
    const communityCoordinates = {
        'Tamale': { lat: 9.4034, lon: -0.8448, radius: 10 },
        'Savelugu': { lat: 9.6223, lon: -0.8295, radius: 8 },
        'Tolon': { lat: 9.4167, lon: -1.0333, radius: 7 },
        'Kumbungu': { lat: 9.5667, lon: -1.0167, radius: 6 },
        'Nanton': { lat: 9.2833, lon: -1.0667, radius: 5 },
        'Karaga': { lat: 9.9333, lon: -0.3333, radius: 8 },
        'Gushegu': { lat: 9.9667, lon: -0.2500, radius: 7 },
        'Zabzugu': { lat: 9.7333, lon: -0.0333, radius: 6 },
        'Yendi': { lat: 9.4500, lon: -0.0167, radius: 10 },
        'Mion': { lat: 9.5667, lon: -0.7000, radius: 6 }
    };

    const communityData = communityCoordinates[community];
    
    if (!communityData) {
        return {
            isValid: false,
            distance: null,
            message: 'Community not found in system'
        };
    }

    const distance = calculateDistance(
        userLat, 
        userLon, 
        communityData.lat, 
        communityData.lon
    );

    const isWithinRadius = distance <= communityData.radius;

    return {
        isValid: isWithinRadius,
        distance: distance.toFixed(2),
        radius: communityData.radius,
        message: isWithinRadius 
            ? `Location verified: ${distance.toFixed(2)}km from ${community} center` 
            : `You are ${distance.toFixed(2)}km away from ${community} (max: ${communityData.radius}km)`
    };
}

/**
 * Submit attendance to backend
 * @param {number} studentId
 * @param {string} date
 * @param {number} latitude
 * @param {number} longitude
 * @returns {Promise<object>}
 */
async function submitAttendance(studentId, date, latitude, longitude) {
    try {
        const response = await fetch(`${DCROP.API_BASE_URL}/api/attendance.php?action=submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: studentId,
                date: date,
                latitude: latitude,
                longitude: longitude
            })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Submit attendance error:', error);
        throw error;
    }
}

/**
 * Get student attendance history
 * @param {number} studentId
 * @returns {Promise<array>}
 */
async function getAttendanceHistory(studentId) {
    try {
        const response = await fetch(
            `${DCROP.API_BASE_URL}/api/attendance.php?action=history&student_id=${studentId}`
        );

        const result = await response.json();
        
        if (result.success) {
            return result.attendance;
        } else {
            throw new Error(result.message || 'Failed to load attendance history');
        }
    } catch (error) {
        console.error('Get attendance history error:', error);
        throw error;
    }
}

/**
 * Get attendance statistics
 * @param {array} attendanceRecords
 * @returns {object}
 */
function calculateAttendanceStats(attendanceRecords) {
    const total = attendanceRecords.length;
    const present = attendanceRecords.filter(a => a.status === 'present').length;
    const absent = attendanceRecords.filter(a => a.status === 'absent').length;
    const pending = attendanceRecords.filter(a => a.status === 'pending').length;
    const verified = attendanceRecords.filter(a => a.verified == 1).length;

    const presentRate = total > 0 ? ((present / total) * 100).toFixed(1) : 0;
    const verificationRate = total > 0 ? ((verified / total) * 100).toFixed(1) : 0;

    return {
        total,
        present,
        absent,
        pending,
        verified,
        presentRate,
        verificationRate
    };
}

/**
 * Filter attendance by date range
 * @param {array} attendanceRecords
 * @param {string} startDate
 * @param {string} endDate
 * @returns {array}
 */
function filterAttendanceByDateRange(attendanceRecords, startDate, endDate) {
    if (!startDate && !endDate) {
        return attendanceRecords;
    }

    return attendanceRecords.filter(record => {
        const recordDate = new Date(record.date);
        const start = startDate ? new Date(startDate) : new Date('1900-01-01');
        const end = endDate ? new Date(endDate) : new Date('2100-12-31');

        return recordDate >= start && recordDate <= end;
    });
}

/**
 * Filter attendance by month
 * @param {array} attendanceRecords
 * @param {string} monthString - Format: YYYY-MM
 * @returns {array}
 */
function filterAttendanceByMonth(attendanceRecords, monthString) {
    if (!monthString) {
        return attendanceRecords;
    }

    return attendanceRecords.filter(record => {
        const recordMonth = record.date.substring(0, 7); // Get YYYY-MM
        return recordMonth === monthString;
    });
}

/**
 * Filter attendance by status
 * @param {array} attendanceRecords
 * @param {string} status - 'present', 'absent', 'pending'
 * @returns {array}
 */
function filterAttendanceByStatus(attendanceRecords, status) {
    if (!status) {
        return attendanceRecords;
    }

    return attendanceRecords.filter(record => record.status === status);
}

/**
 * Get monthly attendance summary
 * @param {array} attendanceRecords
 * @param {string} monthString - Format: YYYY-MM
 * @returns {object}
 */
function getMonthlyAttendanceSummary(attendanceRecords, monthString) {
    const monthRecords = filterAttendanceByMonth(attendanceRecords, monthString);
    const stats = calculateAttendanceStats(monthRecords);

    const monthDate = new Date(monthString + '-01');
    const monthName = monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    return {
        month: monthName,
        monthString: monthString,
        ...stats
    };
}

/**
 * Check if attendance already submitted for date
 * @param {array} attendanceRecords
 * @param {string} date
 * @returns {boolean}
 */
function isAttendanceSubmitted(attendanceRecords, date) {
    return attendanceRecords.some(record => record.date === date);
}

/**
 * Get attendance status color
 * @param {string} status
 * @returns {string}
 */
function getAttendanceStatusColor(status) {
    const colors = {
        'present': '#10b981',
        'absent': '#ef4444',
        'pending': '#f59e0b'
    };
    return colors[status] || '#6b7280';
}

/**
 * Format location coordinates
 * @param {number} latitude
 * @param {number} longitude
 * @returns {string}
 */
function formatCoordinates(latitude, longitude) {
    const lat = parseFloat(latitude).toFixed(6);
    const lon = parseFloat(longitude).toFixed(6);
    return `${lat}, ${lon}`;
}

/**
 * Get map URL for coordinates (Google Maps)
 * @param {number} latitude
 * @param {number} longitude
 * @returns {string}
 */
function getMapURL(latitude, longitude) {
    return `https://www.google.com/maps?q=${latitude},${longitude}`;
}

/**
 * Validate date is not in future
 * @param {string} dateString
 * @returns {boolean}
 */
function isValidAttendanceDate(dateString) {
    const selectedDate = new Date(dateString);
    const today = new Date();
    today.setHours(23, 59, 59, 999); // Set to end of today

    return selectedDate <= today;
}

/**
 * Get attendance streak (consecutive present days)
 * @param {array} attendanceRecords
 * @returns {number}
 */
function getAttendanceStreak(attendanceRecords) {
    if (attendanceRecords.length === 0) return 0;

    // Sort by date descending
    const sorted = [...attendanceRecords].sort((a, b) => 
        new Date(b.date) - new Date(a.date)
    );

    let streak = 0;
    let expectedDate = new Date();

    for (const record of sorted) {
        const recordDate = new Date(record.date);
        
        // Check if this is the expected date
        if (recordDate.toDateString() === expectedDate.toDateString()) {
            if (record.status === 'present') {
                streak++;
                expectedDate.setDate(expectedDate.getDate() - 1);
            } else {
                break;
            }
        } else {
            break;
        }
    }

    return streak;
}

/**
 * Export attendance data to CSV
 * @param {array} attendanceRecords
 * @param {string} filename
 */
function exportAttendanceToCSV(attendanceRecords, filename = 'attendance') {
    const csvData = attendanceRecords.map(record => ({
        'Date': record.date,
        'Status': record.status,
        'Verified': record.verified ? 'Yes' : 'No',
        'Latitude': record.latitude,
        'Longitude': record.longitude,
        'Submitted At': DCROP.formatDateTime(record.created_at)
    }));

    DCROP.downloadCSV(csvData, filename);
}

/**
 * Get attendance for specific date
 * @param {array} attendanceRecords
 * @param {string} date
 * @returns {object|null}
 */
function getAttendanceByDate(attendanceRecords, date) {
    return attendanceRecords.find(record => record.date === date) || null;
}

/**
 * Group attendance by month
 * @param {array} attendanceRecords
 * @returns {object}
 */
function groupAttendanceByMonth(attendanceRecords) {
    const grouped = {};

    attendanceRecords.forEach(record => {
        const month = record.date.substring(0, 7); // YYYY-MM
        
        if (!grouped[month]) {
            grouped[month] = [];
        }
        
        grouped[month].push(record);
    });

    return grouped;
}

/**
 * Get day name from date string
 * @param {string} dateString
 * @returns {string}
 */
function getDayName(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'long' });
}

/**
 * Get short day name from date string
 * @param {string} dateString
 * @returns {string}
 */
function getShortDayName(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short' });
}

/**
 * Check if today's attendance is submitted
 * @param {array} attendanceRecords
 * @returns {boolean}
 */
function isTodayAttendanceSubmitted(attendanceRecords) {
    const today = new Date().toISOString().split('T')[0];
    return isAttendanceSubmitted(attendanceRecords, today);
}

/**
 * Get last attendance date
 * @param {array} attendanceRecords
 * @returns {string|null}
 */
function getLastAttendanceDate(attendanceRecords) {
    if (attendanceRecords.length === 0) return null;

    const sorted = [...attendanceRecords].sort((a, b) => 
        new Date(b.date) - new Date(a.date)
    );

    return sorted[0].date;
}

// Export to global DCROP namespace
if (window.DCROP) {
    window.DCROP.Attendance = {
        getCurrentLocation,
        calculateDistance,
        verifyLocationInCommunity,
        submitAttendance,
        getAttendanceHistory,
        calculateAttendanceStats,
        filterAttendanceByDateRange,
        filterAttendanceByMonth,
        filterAttendanceByStatus,
        getMonthlyAttendanceSummary,
        isAttendanceSubmitted,
        getAttendanceStatusColor,
        formatCoordinates,
        getMapURL,
        isValidAttendanceDate,
        getAttendanceStreak,
        exportAttendanceToCSV,
        getAttendanceByDate,
        groupAttendanceByMonth,
        getDayName,
        getShortDayName,
        isTodayAttendanceSubmitted,
        getLastAttendanceDate
    };
}