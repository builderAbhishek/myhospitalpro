/**
 * MY HOSPITAL PRO — Centralized Mock Data & Database Simulation
 * Schema mirrors future MySQL database structures for seamless PHP conversion.
 */

const SEED_DATA = {
    medicines: [
        {
            id: 1,
            name: "Paracetamol 500mg",
            genericName: "Paracetamol / Acetaminophen",
            brand: "Crocin Advance",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "500 mg",
            packSize: "15 Tablets / Strip",
            manufacturer: "GlaxoSmithKline Pharmaceuticals",
            hsn: "30049060",
            gst: 12,
            mrp: 32.50,
            sellingPrice: 30.00,
            purchasePrice: 21.00,
            minStock: 20,
            maxStock: 500,
            reorderLevel: 50,
            rackNumber: "A-12-04",
            barcode: "8901234567890",
            medicineCode: "MED-0001",
            prescriptionRequired: false,
            scheduleType: "General (OTC)",
            status: "active",
            batches: [
                { id: 101, batchNo: "PCM26A", expiry: "2027-04-30", purchaseRate: 21.00, mrp: 32.50, quantity: 180 },
                { id: 102, batchNo: "PCM25X", expiry: "2026-10-15", purchaseRate: 20.50, mrp: 32.00, quantity: 24 } // Expiring in ~18 days
            ]
        },
        {
            id: 2,
            name: "Azithromycin 500mg",
            genericName: "Azithromycin Dihydrate",
            brand: "Azee 500",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "500 mg",
            packSize: "5 Tablets / Strip",
            manufacturer: "Cipla Ltd",
            hsn: "30042010",
            gst: 12,
            mrp: 119.50,
            sellingPrice: 110.00,
            purchasePrice: 78.00,
            minStock: 10,
            maxStock: 200,
            reorderLevel: 25,
            rackNumber: "B-03-01",
            barcode: "8901234567891",
            medicineCode: "MED-0002",
            prescriptionRequired: true,
            scheduleType: "Schedule H1",
            status: "active",
            batches: [
                { id: 103, batchNo: "AZI2609", expiry: "2027-08-31", purchaseRate: 78.00, mrp: 119.50, quantity: 65 },
                { id: 104, batchNo: "AZI2511", expiry: "2026-11-20", purchaseRate: 76.50, mrp: 118.00, quantity: 12 } // Expiring in ~54 days
            ]
        },
        {
            id: 3,
            name: "Amoxicillin & Potassium Clavulanate 625mg",
            genericName: "Amoxicillin (500mg) + Clavulanic Acid (125mg)",
            brand: "Augmentin 625 Duo",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "625 mg",
            packSize: "10 Tablets / Strip",
            manufacturer: "GlaxoSmithKline Pharmaceuticals",
            hsn: "30041090",
            gst: 12,
            mrp: 205.00,
            sellingPrice: 195.00,
            purchasePrice: 142.00,
            minStock: 15,
            maxStock: 300,
            reorderLevel: 30,
            rackNumber: "B-03-02",
            barcode: "8901234567892",
            medicineCode: "MED-0003",
            prescriptionRequired: true,
            scheduleType: "Schedule H1",
            status: "active",
            batches: [
                { id: 105, batchNo: "AUG26D", expiry: "2027-06-30", purchaseRate: 142.00, mrp: 205.00, quantity: 90 }
            ]
        },
        {
            id: 4,
            name: "Pantoprazole Gastro-Resistant 40mg",
            genericName: "Pantoprazole Sodium",
            brand: "Pan 40",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "40 mg",
            packSize: "15 Tablets / Strip",
            manufacturer: "Alkem Laboratories",
            hsn: "30049099",
            gst: 12,
            mrp: 155.00,
            sellingPrice: 145.00,
            purchasePrice: 98.00,
            minStock: 25,
            maxStock: 400,
            reorderLevel: 40,
            rackNumber: "A-08-02",
            barcode: "8901234567893",
            medicineCode: "MED-0004",
            prescriptionRequired: false,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 106, batchNo: "PAN26E", expiry: "2027-11-30", purchaseRate: 98.00, mrp: 155.00, quantity: 120 }
            ]
        },
        {
            id: 5,
            name: "Cetirizine Hydrochloride 10mg",
            genericName: "Cetirizine HCl",
            brand: "Cetzine 10",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "10 mg",
            packSize: "10 Tablets / Strip",
            manufacturer: "Dr. Reddy's Laboratories",
            hsn: "30049099",
            gst: 12,
            mrp: 22.00,
            sellingPrice: 20.00,
            purchasePrice: 13.50,
            minStock: 30,
            maxStock: 500,
            reorderLevel: 50,
            rackNumber: "A-05-01",
            barcode: "8901234567894",
            medicineCode: "MED-0005",
            prescriptionRequired: false,
            scheduleType: "General (OTC)",
            status: "active",
            batches: [
                { id: 107, batchNo: "CET26A", expiry: "2028-02-28", purchaseRate: 13.50, mrp: 22.00, quantity: 210 }
            ]
        },
        {
            id: 6,
            name: "ORS Electrolyte Powder 21.8g",
            genericName: "Oral Rehydration Salts (WHO Formula)",
            brand: "Electral Powder",
            category: "Powder / Sachet",
            dosageForm: "Sachet",
            strength: "21.8 g",
            packSize: "1 Sachet",
            manufacturer: "FDC Limited",
            hsn: "30049011",
            gst: 5,
            mrp: 23.50,
            sellingPrice: 22.00,
            purchasePrice: 16.00,
            minStock: 50,
            maxStock: 1000,
            reorderLevel: 80,
            rackNumber: "C-01-01",
            barcode: "8901234567895",
            medicineCode: "MED-0006",
            prescriptionRequired: false,
            scheduleType: "General (OTC)",
            status: "active",
            batches: [
                { id: 108, batchNo: "ELE2602", expiry: "2027-10-31", purchaseRate: 16.00, mrp: 23.50, quantity: 15 } // LOW STOCK (15 < 80)
            ]
        },
        {
            id: 7,
            name: "Cholecalciferol (Vitamin D3) 60,000 IU",
            genericName: "Cholecalciferol",
            brand: "Calcirol 60K Granules",
            category: "Powder / Sachet",
            dosageForm: "Sachet",
            strength: "60000 IU / 1g",
            packSize: "4 Sachets / Pack",
            manufacturer: "Cadila Pharmaceuticals",
            hsn: "30045020",
            gst: 12,
            mrp: 180.00,
            sellingPrice: 170.00,
            purchasePrice: 115.00,
            minStock: 20,
            maxStock: 250,
            reorderLevel: 35,
            rackNumber: "A-02-04",
            barcode: "8901234567896",
            medicineCode: "MED-0007",
            prescriptionRequired: false,
            scheduleType: "General (OTC)",
            status: "active",
            batches: [
                { id: 109, batchNo: "CAL26G", expiry: "2027-05-31", purchaseRate: 115.00, mrp: 180.00, quantity: 45 }
            ]
        },
        {
            id: 8,
            name: "Metformin Hydrochloride 500mg",
            genericName: "Metformin HCl",
            brand: "Glycomet 500",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "500 mg",
            packSize: "20 Tablets / Strip",
            manufacturer: "USV Private Limited",
            hsn: "30049099",
            gst: 12,
            mrp: 44.00,
            sellingPrice: 40.00,
            purchasePrice: 28.00,
            minStock: 40,
            maxStock: 600,
            reorderLevel: 60,
            rackNumber: "D-04-01",
            barcode: "8901234567897",
            medicineCode: "MED-0008",
            prescriptionRequired: true,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 110, batchNo: "GLY26K", expiry: "2027-09-30", purchaseRate: 28.00, mrp: 44.00, quantity: 8 } // LOW STOCK (8 < 60)
            ]
        },
        {
            id: 9,
            name: "Atorvastatin 10mg",
            genericName: "Atorvastatin Calcium",
            brand: "Atorva 10",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "10 mg",
            packSize: "15 Tablets / Strip",
            manufacturer: "Zydus Lifesciences",
            hsn: "30049099",
            gst: 12,
            mrp: 98.00,
            sellingPrice: 90.00,
            purchasePrice: 62.00,
            minStock: 20,
            maxStock: 300,
            reorderLevel: 30,
            rackNumber: "D-04-02",
            barcode: "8901234567898",
            medicineCode: "MED-0009",
            prescriptionRequired: true,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 111, batchNo: "ATO26M", expiry: "2027-12-31", purchaseRate: 62.00, mrp: 98.00, quantity: 50 },
                { id: 112, batchNo: "ATO24Old", expiry: "2026-08-30", purchaseRate: 60.00, mrp: 95.00, quantity: 6 } // EXPIRED
            ]
        },
        {
            id: 10,
            name: "Telmisartan 40mg",
            genericName: "Telmisartan",
            brand: "Telma 40",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "40 mg",
            packSize: "30 Tablets / Strip",
            manufacturer: "Glenmark Pharmaceuticals",
            hsn: "30049099",
            gst: 12,
            mrp: 220.00,
            sellingPrice: 205.00,
            purchasePrice: 145.00,
            minStock: 15,
            maxStock: 250,
            reorderLevel: 25,
            rackNumber: "D-05-01",
            barcode: "8901234567899",
            medicineCode: "MED-0010",
            prescriptionRequired: true,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 113, batchNo: "TEL26P", expiry: "2027-07-31", purchaseRate: 145.00, mrp: 220.00, quantity: 42 }
            ]
        },
        {
            id: 11,
            name: "Montelukast 10mg & Levocetirizine 5mg",
            genericName: "Montelukast + Levocetirizine",
            brand: "Montair LC",
            category: "Tablet",
            dosageForm: "Tablet",
            strength: "10mg + 5mg",
            packSize: "15 Tablets / Strip",
            manufacturer: "Cipla Ltd",
            hsn: "30049099",
            gst: 12,
            mrp: 310.00,
            sellingPrice: 290.00,
            purchasePrice: 210.00,
            minStock: 20,
            maxStock: 300,
            reorderLevel: 30,
            rackNumber: "A-06-03",
            barcode: "8901234567900",
            medicineCode: "MED-0011",
            prescriptionRequired: true,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 114, batchNo: "MNT26Q", expiry: "2028-01-31", purchaseRate: 210.00, mrp: 310.00, quantity: 0 } // OUT OF STOCK
            ]
        },
        {
            id: 12,
            name: "Cough Syrup with Ambroxol & Guaiphenesin 100ml",
            genericName: "Ambroxol + Levosalbutamol + Guaiphenesin",
            brand: "Ascoril LS Syrup",
            category: "Syrup / Liquid",
            dosageForm: "Syrup",
            strength: "100 ml",
            packSize: "1 Bottle",
            manufacturer: "Glenmark Pharmaceuticals",
            hsn: "30049099",
            gst: 12,
            mrp: 124.00,
            sellingPrice: 118.00,
            purchasePrice: 84.00,
            minStock: 15,
            maxStock: 200,
            reorderLevel: 25,
            rackNumber: "S-02-01",
            barcode: "8901234567901",
            medicineCode: "MED-0012",
            prescriptionRequired: false,
            scheduleType: "Schedule H",
            status: "active",
            batches: [
                { id: 115, batchNo: "ASC26B", expiry: "2027-03-31", purchaseRate: 84.00, mrp: 124.00, quantity: 38 }
            ]
        }
    ],

    suppliers: [
        {
            id: 1,
            name: "Apollo Healthcare Distributors Pvt Ltd",
            company: "Apollo Health Logistics Division",
            contactPerson: "Sanjay Singhal",
            phone: "+91 98112 34567",
            email: "orders@apollodist.in",
            address: "Building 14, Okhla Industrial Area Phase 3",
            city: "New Delhi",
            state: "Delhi",
            pincode: "110020",
            gstin: "07AAACA1234F1Z8",
            drugLicence: "DL-20B-112233 / 21B-112234",
            paymentTerms: "30 Days Net",
            totalPurchases: 284500,
            outstandingBalance: 14200,
            status: "active"
        },
        {
            id: 2,
            name: "MedPlus Logistics & Supply",
            company: "MedPlus Regional Depo",
            contactPerson: "Rameshwar Rao",
            phone: "+91 97234 56789",
            email: "delhi.depot@medpluslog.com",
            address: "Plot 88, Udyog Vihar Phase 4",
            city: "Gurugram",
            state: "Haryana",
            pincode: "122015",
            gstin: "06AABCM5678K1Z2",
            drugLicence: "HR-20B-445566 / 21B-445567",
            paymentTerms: "15 Days Net",
            totalPurchases: 198400,
            outstandingBalance: 0,
            status: "active"
        },
        {
            id: 3,
            name: "Cipla Trade Distribution Agency",
            company: "Cipla Authorized Stockist",
            contactPerson: "Harish Chawla",
            phone: "+91 98990 11223",
            email: "cipla.stockist@chawlamedical.in",
            address: "24, Bhagirath Palace, Chandni Chowk",
            city: "Delhi",
            state: "Delhi",
            pincode: "110006",
            gstin: "07AAAC7890D1Z1",
            drugLicence: "DL-20B-778899 / 21B-778800",
            paymentTerms: "21 Days Net",
            totalPurchases: 432900,
            outstandingBalance: 18650,
            status: "active"
        },
        {
            id: 4,
            name: "Vardhman Medical Agency",
            company: "Vardhman Pharma Network",
            contactPerson: "Vikas Jain",
            phone: "+91 93122 88441",
            email: "vikas@vardhmanpharma.com",
            address: "Shop 12-14, Dawa Bazaar, Karol Bagh",
            city: "New Delhi",
            state: "Delhi",
            pincode: "110005",
            gstin: "07AABFV4512H1ZQ",
            drugLicence: "DL-20B-998811 / 21B-998822",
            paymentTerms: "Immediate / 7 Days",
            totalPurchases: 145000,
            outstandingBalance: 0,
            status: "active"
        }
    ],

    customers: [
        {
            id: 1,
            name: "Rajesh Sharma",
            mobile: "+91 98101 23456",
            email: "rajesh.sharma@gmail.com",
            address: "B-4/102, Sector 14",
            city: "Rohini, Delhi",
            customerType: "Regular Patient",
            creditLimit: 5000,
            totalPurchases: 14250,
            outstanding: 850,
            lastPurchase: "2026-09-25",
            status: "active"
        },
        {
            id: 2,
            name: "Priya Patel",
            mobile: "+91 98250 87654",
            email: "priya.patel@yahoo.com",
            address: "Flat 302, Palm Heights",
            city: "Pitampura, Delhi",
            customerType: "Regular Patient",
            creditLimit: 3000,
            totalPurchases: 8900,
            outstanding: 0,
            lastPurchase: "2026-09-26",
            status: "active"
        },
        {
            id: 3,
            name: "Amit Verma",
            mobile: "+91 99112 33445",
            email: "amit.verma@outlook.com",
            address: "House 54, Block C",
            city: "Prashant Vihar, Delhi",
            customerType: "VIP / Hospital Staff",
            creditLimit: 10000,
            totalPurchases: 22400,
            outstanding: 1450,
            lastPurchase: "2026-09-27",
            status: "active"
        },
        {
            id: 4,
            name: "Sunita Devi",
            mobile: "+91 98711 00998",
            email: "",
            address: "Village Rithala",
            city: "Delhi",
            customerType: "Chronic Care Patient",
            creditLimit: 2000,
            totalPurchases: 6200,
            outstanding: 400,
            lastPurchase: "2026-09-24",
            status: "active"
        },
        {
            id: 5,
            name: "Dr. Vikram Singh (Clinic OPD)",
            mobile: "+91 98188 55443",
            email: "drvikram@hospitalopd.in",
            address: "OPD Chamber 4, Hospital Wing B",
            city: "Rohini, Delhi",
            customerType: "Corporate / Institutional",
            creditLimit: 50000,
            totalPurchases: 68000,
            outstanding: 12000,
            lastPurchase: "2026-09-27",
            status: "active"
        }
    ],

    purchases: [
        {
            id: 1,
            invoiceNo: "PUR-2026-0891",
            supplierId: 1,
            supplierName: "Apollo Healthcare Distributors Pvt Ltd",
            invoiceDate: "2026-09-22",
            paymentType: "Credit",
            paymentStatus: "Pending",
            itemsCount: 4,
            subtotal: 16400.00,
            discount: 500.00,
            gst: 1908.00,
            roundOff: 0.00,
            totalAmount: 17808.00,
            paidAmount: 3608.00,
            dueAmount: 14200.00,
            items: [
                { medicineId: 1, name: "Paracetamol 500mg", batchNo: "PCM26A", expiry: "2027-04-30", qty: 200, free: 20, rate: 21.00, mrp: 32.50, gst: 12, discount: 0, total: 4200.00 },
                { medicineId: 4, name: "Pantoprazole Gastro-Resistant 40mg", batchNo: "PAN26E", expiry: "2027-11-30", qty: 100, free: 0, rate: 98.00, mrp: 155.00, gst: 12, discount: 5, total: 9310.00 }
            ]
        },
        {
            id: 2,
            invoiceNo: "PUR-2026-0870",
            supplierId: 3,
            supplierName: "Cipla Trade Distribution Agency",
            invoiceDate: "2026-09-18",
            paymentType: "Bank Transfer / NEFT",
            paymentStatus: "Pending",
            itemsCount: 3,
            subtotal: 21450.00,
            discount: 800.00,
            gst: 2478.00,
            roundOff: 0.00,
            totalAmount: 23128.00,
            paidAmount: 4478.00,
            dueAmount: 18650.00,
            items: [
                { medicineId: 2, name: "Azithromycin 500mg", batchNo: "AZI2609", expiry: "2027-08-31", qty: 80, free: 5, rate: 78.00, mrp: 119.50, gst: 12, discount: 0, total: 6240.00 },
                { medicineId: 3, name: "Amoxicillin & Clavulanate 625mg", batchNo: "AUG26D", expiry: "2027-06-30", qty: 100, free: 0, rate: 142.00, mrp: 205.00, gst: 12, discount: 0, total: 14200.00 }
            ]
        },
        {
            id: 3,
            invoiceNo: "PUR-2026-0842",
            supplierId: 2,
            supplierName: "MedPlus Logistics & Supply",
            invoiceDate: "2026-09-10",
            paymentType: "Cheque",
            paymentStatus: "Paid",
            itemsCount: 5,
            subtotal: 18420.00,
            discount: 420.00,
            gst: 2160.00,
            roundOff: 0.00,
            totalAmount: 20160.00,
            paidAmount: 20160.00,
            dueAmount: 0.00,
            items: [
                { medicineId: 5, name: "Cetirizine 10mg", batchNo: "CET26A", expiry: "2028-02-28", qty: 250, free: 25, rate: 13.50, mrp: 22.00, gst: 12, discount: 0, total: 3375.00 }
            ]
        }
    ],

    sales: [
        {
            id: 1,
            invoiceNo: "INV-2026-0042",
            date: "2026-09-27T10:15:00",
            customerId: 1,
            customerName: "Rajesh Sharma",
            customerMobile: "+91 98101 23456",
            doctorName: "Dr. K. Singhal (Cardiology)",
            paymentMethod: "UPI / PhonePe",
            paymentStatus: "Paid",
            itemsCount: 3,
            subtotal: 610.00,
            discount: 30.00,
            tax: 69.60,
            roundOff: 0.40,
            grandTotal: 650.00,
            amountPaid: 650.00,
            balance: 0.00,
            items: [
                { medicineId: 1, name: "Paracetamol 500mg", batchNo: "PCM26A", exp: "2027-04", qty: 2, rate: 30.00, mrp: 32.50, discount: 0, gst: 12, total: 60.00 },
                { medicineId: 4, name: "Pantoprazole 40mg", batchNo: "PAN26E", exp: "2027-11", qty: 2, rate: 145.00, mrp: 155.00, discount: 10, gst: 12, total: 290.00 },
                { medicineId: 3, name: "Amoxicillin & Clavulanate 625mg", batchNo: "AUG26D", exp: "2027-06", qty: 1, rate: 195.00, mrp: 205.00, discount: 0, gst: 12, total: 195.00 }
            ]
        },
        {
            id: 2,
            invoiceNo: "INV-2026-0041",
            date: "2026-09-27T09:42:00",
            customerId: 2,
            customerName: "Priya Patel",
            customerMobile: "+91 98250 87654",
            doctorName: "Dr. M. Iyer (Medicine)",
            paymentMethod: "Cash",
            paymentStatus: "Paid",
            itemsCount: 2,
            subtotal: 330.00,
            discount: 10.00,
            tax: 38.40,
            roundOff: 0.00,
            grandTotal: 358.40,
            amountPaid: 360.00,
            balance: 0.00,
            items: [
                { medicineId: 2, name: "Azithromycin 500mg", batchNo: "AZI2609", exp: "2027-08", qty: 1, rate: 110.00, mrp: 119.50, discount: 0, gst: 12, total: 110.00 },
                { medicineId: 5, name: "Cetirizine 10mg", batchNo: "CET26A", exp: "2028-02", qty: 2, rate: 20.00, mrp: 22.00, discount: 0, gst: 12, total: 40.00 }
            ]
        },
        {
            id: 3,
            invoiceNo: "INV-2026-0040",
            date: "2026-09-27T09:10:00",
            customerId: null,
            customerName: "Walk-in Customer",
            customerMobile: "",
            doctorName: "",
            paymentMethod: "Cash",
            paymentStatus: "Paid",
            itemsCount: 1,
            subtotal: 44.00,
            discount: 0.00,
            tax: 2.20,
            roundOff: -0.20,
            grandTotal: 46.00,
            amountPaid: 50.00,
            balance: 0.00,
            items: [
                { medicineId: 6, name: "ORS Electrolyte Powder 21.8g", batchNo: "ELE2602", exp: "2027-10", qty: 2, rate: 22.00, mrp: 23.50, discount: 0, gst: 5, total: 44.00 }
            ]
        },
        {
            id: 4,
            invoiceNo: "INV-2026-0039",
            date: "2026-09-26T18:20:00",
            customerId: 3,
            customerName: "Amit Verma",
            customerMobile: "+91 99112 33445",
            doctorName: "Dr. Arvind Sharma",
            paymentMethod: "Credit Card",
            paymentStatus: "Paid",
            itemsCount: 3,
            subtotal: 820.00,
            discount: 40.00,
            tax: 93.60,
            roundOff: 0.00,
            grandTotal: 873.60,
            amountPaid: 873.60,
            balance: 0.00,
            items: [
                { medicineId: 7, name: "Vitamin D3 60K", batchNo: "CAL26G", exp: "2027-05", qty: 2, rate: 170.00, mrp: 180.00, discount: 0, gst: 12, total: 340.00 },
                { medicineId: 10, name: "Telmisartan 40mg", batchNo: "TEL26P", exp: "2027-07", qty: 2, rate: 205.00, mrp: 220.00, discount: 0, gst: 12, total: 410.00 }
            ]
        },
        {
            id: 5,
            invoiceNo: "INV-2026-0038",
            date: "2026-09-26T14:10:00",
            customerId: 5,
            customerName: "Dr. Vikram Singh (Clinic OPD)",
            customerMobile: "+91 98188 55443",
            doctorName: "Self / Clinic OPD Use",
            paymentMethod: "Credit / Ledger",
            paymentStatus: "Pending",
            itemsCount: 4,
            subtotal: 4200.00,
            discount: 200.00,
            tax: 480.00,
            roundOff: 0.00,
            grandTotal: 4480.00,
            amountPaid: 0.00,
            balance: 4480.00,
            items: [
                { medicineId: 1, name: "Paracetamol 500mg", batchNo: "PCM26A", exp: "2027-04", qty: 20, rate: 30.00, mrp: 32.50, discount: 0, gst: 12, total: 600.00 },
                { medicineId: 3, name: "Amoxicillin & Clavulanate 625mg", batchNo: "AUG26D", exp: "2027-06", qty: 10, rate: 195.00, mrp: 205.00, discount: 0, gst: 12, total: 1950.00 }
            ]
        }
    ],

    adjustments: [
        {
            id: 1,
            date: "2026-09-25",
            medicineId: 9,
            medicineName: "Atorvastatin 10mg",
            batchNo: "ATO24Old",
            adjustmentType: "Expired",
            quantity: 6,
            reason: "Batch expired past shelf date",
            notes: "Moved to expiry quarantine bin for manufacturer return credit",
            adjustedBy: "Dr. Arvind Sharma"
        },
        {
            id: 2,
            date: "2026-09-24",
            medicineId: 12,
            medicineName: "Ascoril LS Syrup 100ml",
            batchNo: "ASC26B",
            adjustmentType: "Damage",
            quantity: 2,
            reason: "Bottle cap seal shattered during shelf unpacking",
            notes: "Written off to store breakage account",
            adjustedBy: "Store Pharmacist"
        }
    ]
};

// Database persistence wrapper
const MHP_DB = {
    init() {
        if (!localStorage.getItem("mhp_initialized")) {
            localStorage.setItem("mhp_medicines", JSON.stringify(SEED_DATA.medicines));
            localStorage.setItem("mhp_suppliers", JSON.stringify(SEED_DATA.suppliers));
            localStorage.setItem("mhp_customers", JSON.stringify(SEED_DATA.customers));
            localStorage.setItem("mhp_purchases", JSON.stringify(SEED_DATA.purchases));
            localStorage.setItem("mhp_sales", JSON.stringify(SEED_DATA.sales));
            localStorage.setItem("mhp_adjustments", JSON.stringify(SEED_DATA.adjustments));
            localStorage.setItem("mhp_initialized", "true");
        }
    },

    reset() {
        localStorage.removeItem("mhp_initialized");
        localStorage.removeItem("mhp_medicines");
        localStorage.removeItem("mhp_suppliers");
        localStorage.removeItem("mhp_customers");
        localStorage.removeItem("mhp_purchases");
        localStorage.removeItem("mhp_sales");
        localStorage.removeItem("mhp_adjustments");
        this.init();
    },

    // Medicines
    getMedicines() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_medicines") || "[]");
    },

    getMedicineById(id) {
        const meds = this.getMedicines();
        return meds.find(m => Number(m.id) === Number(id));
    },

    saveMedicine(med) {
        const meds = this.getMedicines();
        if (med.id) {
            const idx = meds.findIndex(m => Number(m.id) === Number(med.id));
            if (idx >= 0) meds[idx] = { ...meds[idx], ...med };
        } else {
            med.id = meds.length ? Math.max(...meds.map(m => Number(m.id))) + 1 : 1;
            med.batches = med.batches || [];
            med.status = med.status || 'active';
            meds.unshift(med);
        }
        localStorage.setItem("mhp_medicines", JSON.stringify(meds));
        return med;
    },

    deleteMedicine(id) {
        let meds = this.getMedicines();
        meds = meds.filter(m => Number(m.id) !== Number(id));
        localStorage.setItem("mhp_medicines", JSON.stringify(meds));
    },

    // Suppliers
    getSuppliers() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_suppliers") || "[]");
    },

    getSupplierById(id) {
        return this.getSuppliers().find(s => Number(s.id) === Number(id));
    },

    saveSupplier(supp) {
        const list = this.getSuppliers();
        if (supp.id) {
            const idx = list.findIndex(s => Number(s.id) === Number(supp.id));
            if (idx >= 0) list[idx] = { ...list[idx], ...supp };
        } else {
            supp.id = list.length ? Math.max(...list.map(s => Number(s.id))) + 1 : 1;
            list.unshift(supp);
        }
        localStorage.setItem("mhp_suppliers", JSON.stringify(list));
        return supp;
    },

    // Customers
    getCustomers() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_customers") || "[]");
    },

    getCustomerById(id) {
        return this.getCustomers().find(c => Number(c.id) === Number(id));
    },

    saveCustomer(cust) {
        const list = this.getCustomers();
        if (cust.id) {
            const idx = list.findIndex(c => Number(c.id) === Number(cust.id));
            if (idx >= 0) list[idx] = { ...list[idx], ...cust };
        } else {
            cust.id = list.length ? Math.max(...list.map(c => Number(c.id))) + 1 : 1;
            list.unshift(cust);
        }
        localStorage.setItem("mhp_customers", JSON.stringify(list));
        return cust;
    },

    // Purchases
    getPurchases() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_purchases") || "[]");
    },

    getPurchaseById(id) {
        const list = this.getPurchases();
        return list.find(p => Number(p.id) === Number(id) || p.invoiceNo === id);
    },

    savePurchase(pur) {
        const list = this.getPurchases();
        pur.id = list.length ? Math.max(...list.map(p => Number(p.id))) + 1 : 1;
        list.unshift(pur);
        localStorage.setItem("mhp_purchases", JSON.stringify(list));

        // Update inventory stock for each purchased item
        const meds = this.getMedicines();
        if (pur.items && Array.isArray(pur.items)) {
            pur.items.forEach(item => {
                const med = meds.find(m => Number(m.id) === Number(item.medicineId));
                if (med) {
                    med.batches = med.batches || [];
                    const existingBatch = med.batches.find(b => b.batchNo === item.batchNo);
                    const incomingQty = Number(item.qty || 0) + Number(item.free || 0);
                    if (existingBatch) {
                        existingBatch.quantity = (Number(existingBatch.quantity) || 0) + incomingQty;
                    } else {
                        med.batches.push({
                            id: Math.floor(1000 + Math.random() * 9000),
                            batchNo: item.batchNo,
                            expiry: item.expiry,
                            purchaseRate: item.rate,
                            mrp: item.mrp,
                            quantity: incomingQty
                        });
                    }
                }
            });
            localStorage.setItem("mhp_medicines", JSON.stringify(meds));
        }

        // Update supplier outstanding balance
        if (pur.dueAmount && Number(pur.dueAmount) > 0) {
            const supps = this.getSuppliers();
            const supp = supps.find(s => Number(s.id) === Number(pur.supplierId));
            if (supp) {
                supp.outstandingBalance = (Number(supp.outstandingBalance) || 0) + Number(pur.dueAmount);
                supp.totalPurchases = (Number(supp.totalPurchases) || 0) + Number(pur.totalAmount);
                localStorage.setItem("mhp_suppliers", JSON.stringify(supps));
            }
        }

        return pur;
    },

    // Sales
    getSales() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_sales") || "[]");
    },

    getSaleById(id) {
        const list = this.getSales();
        return list.find(s => Number(s.id) === Number(id) || s.invoiceNo === id);
    },

    saveSale(sale) {
        const list = this.getSales();
        sale.id = list.length ? Math.max(...list.map(s => Number(s.id))) + 1 : 1;
        list.unshift(sale);
        localStorage.setItem("mhp_sales", JSON.stringify(list));

        // Deduct inventory stock
        const meds = this.getMedicines();
        if (sale.items && Array.isArray(sale.items)) {
            sale.items.forEach(item => {
                const med = meds.find(m => Number(m.id) === Number(item.medicineId));
                if (med && med.batches) {
                    const batch = med.batches.find(b => b.batchNo === item.batchNo);
                    if (batch) {
                        batch.quantity = Math.max(0, (Number(batch.quantity) || 0) - Number(item.qty || 0));
                    }
                }
            });
            localStorage.setItem("mhp_medicines", JSON.stringify(meds));
        }

        // Update customer purchase & balance if linked
        if (sale.customerId) {
            const custs = this.getCustomers();
            const cust = custs.find(c => Number(c.id) === Number(sale.customerId));
            if (cust) {
                cust.totalPurchases = (Number(cust.totalPurchases) || 0) + Number(sale.grandTotal);
                cust.lastPurchase = Utils.getTodayISO();
                if (Number(sale.balance) > 0) {
                    cust.outstanding = (Number(cust.outstanding) || 0) + Number(sale.balance);
                }
                localStorage.setItem("mhp_customers", JSON.stringify(custs));
            }
        }

        return sale;
    },

    // Adjustments
    getAdjustments() {
        this.init();
        return JSON.parse(localStorage.getItem("mhp_adjustments") || "[]");
    },

    saveAdjustment(adj) {
        const list = this.getAdjustments();
        adj.id = list.length ? Math.max(...list.map(a => Number(a.id))) + 1 : 1;
        adj.date = adj.date || Utils.getTodayISO();
        list.unshift(adj);
        localStorage.setItem("mhp_adjustments", JSON.stringify(list));

        // Apply adjustment to medicine batch stock
        const meds = this.getMedicines();
        const med = meds.find(m => Number(m.id) === Number(adj.medicineId));
        if (med && med.batches) {
            const batch = med.batches.find(b => b.batchNo === adj.batchNo);
            if (batch) {
                const delta = Number(adj.quantity) || 0;
                // Damage, Expired, Missing reduce stock; Manual correction / Return can increase or decrease
                if (["Damage", "Expired", "Missing"].includes(adj.adjustmentType)) {
                    batch.quantity = Math.max(0, (Number(batch.quantity) || 0) - delta);
                } else if (adj.adjustmentType === "Return") {
                    batch.quantity = (Number(batch.quantity) || 0) + delta;
                } else if (adj.adjustmentType === "Manual Correction") {
                    batch.quantity = delta; // sets exact new counted stock
                }
                localStorage.setItem("mhp_medicines", JSON.stringify(meds));
            }
        }

        return adj;
    }
};

// Initialize database
MHP_DB.init();
