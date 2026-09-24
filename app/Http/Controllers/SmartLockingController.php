<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SmartLockingController extends Controller
{
    /**
     * Display all RFID cards and door access
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        // Mock RFID card data
        $cards = collect([
            (object)[
                'id' => 1,
                'name' => 'Prof. Maria Santos',
                'department' => 'Computer Science',
                'email' => 'maria.santos@university.edu',
                'cardNumber' => '0012345678',
                'rfid' => 'RFID-A1B2C3D4E5F6',
                'status' => 'active',
                'expiryDate' => '2026-03-31',
                'room' => 'CS Lab 301',
                'lastAccess' => '2 hours ago',
                'accessCount' => 342,
            ],
            (object)[
                'id' => 2,
                'name' => 'Dr. Roberto Cruz',
                'department' => 'Engineering',
                'email' => 'roberto.cruz@university.edu',
                'cardNumber' => '0023456789',
                'rfid' => 'RFID-B2C3D4E5F6A1',
                'status' => 'active',
                'expiryDate' => '2026-03-31',
                'room' => 'Engineering Lab 401',
                'lastAccess' => '30 minutes ago',
                'accessCount' => 456,
            ],
            (object)[
                'id' => 3,
                'name' => 'Prof. Ana Reyes',
                'department' => 'Business Administration',
                'email' => 'ana.reyes@university.edu',
                'cardNumber' => '0034567890',
                'rfid' => 'RFID-C3D4E5F6A1B2',
                'status' => 'active',
                'expiryDate' => '2026-03-31',
                'room' => 'Business Room 203',
                'lastAccess' => '5 minutes ago',
                'accessCount' => 218,
            ],
            (object)[
                'id' => 4,
                'name' => 'Dr. Carlos Mendoza',
                'department' => 'Science',
                'email' => 'carlos.mendoza@university.edu',
                'cardNumber' => '0045678901',
                'rfid' => 'RFID-D4E5F6A1B2C3',
                'status' => 'inactive',
                'expiryDate' => '2025-12-31',
                'room' => 'Science Lab 105',
                'lastAccess' => '1 week ago',
                'accessCount' => 127,
            ],
            (object)[
                'id' => 5,
                'name' => 'Prof. Elena Torres',
                'department' => 'Arts',
                'email' => 'elena.torres@university.edu',
                'cardNumber' => '0056789012',
                'rfid' => 'RFID-E5F6A1B2C3D4',
                'status' => 'active',
                'expiryDate' => '2026-06-30',
                'room' => 'Arts Building 210',
                'lastAccess' => '1 day ago',
                'accessCount' => 89,
            ],
            (object)[
                'id' => 6,
                'name' => 'Dr. Juan Dela Cruz',
                'department' => 'Medicine',
                'email' => 'juan.delacruz@university.edu',
                'cardNumber' => '0067890123',
                'rfid' => 'RFID-F6A1B2C3D4E5',
                'status' => 'pending',
                'expiryDate' => '2026-02-28',
                'room' => 'Medical Lab 502',
                'lastAccess' => 'Never',
                'accessCount' => 0,
            ],
        ]);

        // Filter cards by status
        if ($filter !== 'all') {
            $cards = $cards->filter(function ($card) use ($filter) {
                return $card->status === $filter;
            });
        }

        return view('frontend.admin.smartlocking', [
            'cards' => $cards,
            'filter' => $filter,
        ]);
    }

    /**
     * Show details of a specific RFID card
     */
    public function show($id)
    {
        // Mock data for individual card
        $card = [
            'id' => $id,
            'name' => 'Prof. Maria Santos',
            'department' => 'Computer Science',
            'email' => 'maria.santos@university.edu',
            'phone' => '+1 (555) 123-4567',
            'cardNumber' => '0012345678',
            'rfid' => 'RFID-A1B2C3D4E5F6',
            'status' => 'active',
            'expiryDate' => '2026-03-31',
            'room' => 'CS Lab 301',
            'building' => 'Computer Science Building',
            'floor' => '3rd Floor',
            'lastAccess' => '2 hours ago',
            'lastAccessRoom' => 'CS Lab 301',
            'totalAccess' => 342,
            'thisMonth' => 28,
            'accessLog' => [
                ['date' => 'Today 14:35', 'room' => 'CS Lab 301', 'status' => 'Entry'],
                ['date' => 'Today 12:15', 'room' => 'CS Lab 301', 'status' => 'Exit'],
                ['date' => 'Today 09:00', 'room' => 'CS Lab 301', 'status' => 'Entry'],
                ['date' => 'March 15 16:45', 'room' => 'Main Office', 'status' => 'Exit'],
                ['date' => 'March 15 09:30', 'room' => 'Main Office', 'status' => 'Entry'],
            ],
            'schedule' => [
                ['day' => 'Monday', 'time' => '08:00 - 10:00', 'room' => 'CS Lab 301'],
                ['day' => 'Wednesday', 'time' => '13:00 - 15:00', 'room' => 'CS Lab 301'],
                ['day' => 'Friday', 'time' => '10:00 - 12:00', 'room' => 'CS Lab 301'],
            ],
            'authorizedRooms' => [
                ['room' => 'CS Lab 301', 'building' => 'Computer Science Building'],
                ['room' => 'Main Office', 'building' => 'Administration Building'],
                ['room' => 'Library Lab', 'building' => 'Library Building'],
                ['room' => 'Conference Room A', 'building' => 'Computer Science Building'],
            ],
        ];

        return view('frontend.admin.smartlocking-detail', [
            'card' => $card,
        ]);
    }
}
