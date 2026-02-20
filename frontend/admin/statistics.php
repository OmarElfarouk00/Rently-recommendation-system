<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
// Get report data
$totalProperties = getTotalProperties();
$activeRentals = getActiveRentals();
$pendingNegotiations = getPendingNegotiations();
$registeredClients = getRegisteredClients();
$registeredPropertyOwners = getPropertyOwners();
$propertyTypeData = getPropertyTypeDistribution();
$monthlyStats = getMonthlyRentalStats();
$reportStats = getMonthlyPropertyReports();
$VIPPropertyOwners = getTotalVIPPropertyOwners();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Rental Platform</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Reports & Analytics</h1>
                    <p>Comprehensive insights into your rental platform performance</p>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
    <div class="report-header">
        <h3>Property Overview</h3>
        <button class="btn btn-sm btn-primary" onclick="exportSection('property')">
            <i class="fas fa-download"></i>
            Export
        </button>
    </div>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $totalProperties; ?></span>
                                <span class="stat-label">Total Properties</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $activeRentals; ?></span>
                                <span class="stat-label">Active Rentals</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $totalProperties - $activeRentals; ?></span>
                                <span class="stat-label">Available</span>
                            </div>
                        </div>
                        <br>
                        <hr>
                        <br>
    <div class="report-header">
        <h3>Client Analytics</h3>
        <button class="btn btn-sm btn-primary" onclick="exportSection('client')">
            <i class="fas fa-download"></i>
            Export
        </button>
    </div>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $registeredClients; ?></span>
                                <span class="stat-label">Total Clients</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $pendingNegotiations; ?></span>
                                <span class="stat-label">Pending Negotiations</span>
                            </div>
                        </div>
                        <br>
                        <hr>
                        <br>
    <div class="report-header">
        <h3>Property Owners Analytics</h3>
        <button class="btn btn-sm btn-primary" onclick="exportSection('owner')">
            <i class="fas fa-download"></i>
            Export
        </button>
    </div>
                        <div class="report-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $registeredPropertyOwners; ?></span>
                                <span class="stat-label">Total Property Owners</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $VIPPropertyOwners; ?></span>
                                <span class="stat-label">Total VIP Property Owners</span>
                            </div>
                        </div>

                    </div>


                    <!-- <div class="report-card chart-card">
                        <div class="report-header">
                            <h3>Property Type Distribution</h3>
                        </div>
                        <canvas id="propertyTypeChart"></canvas>
                    </div>
 -->
                    <!-- Charts Section -->
                    <div class="chart-card"
                        style="display: flex; flex-direction: column; align-items: center; padding: 1rem;width: 100%; max-width: 600px; background: #fff; border-radius: 10px; padding: 1rem; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem;">Property Type Distribution</h3>
                        <div style="position: relative; height: 470px;">
                            <canvas id="propertyTypeChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card"
                        style="display: flex; flex-direction: column; align-items: center; padding: 1rem;width: fit-content; max-width: fit-content; background: #fff; border-radius: 10px; padding: 1rem; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem;">Monthly Rental Trend</h3>
                        <div style="position: relative; height: 470px; max-width: fit-content;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card"
                        style="display: flex; flex-direction: column; align-items: center; padding: 1rem;width: fit-content; max-width: fit-content; background: #fff; border-radius: 10px; padding: 1rem; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem;">Monthly Reports Trend</h3>
                        <div style="position: relative; height: 470px; max-width: fit-content;">
                            <canvas id="reportChart" style="height: 100% !important; width: 100%;"></canvas>
                        </div>
                    </div>

                    <!-- <div class="report-card chart-card">
                        <div class="report-header">
                            <h3>Monthly Revenue Trend</h3>
                        </div>
                        <canvas id="revenueChart"></canvas>
                    </div> -->
                </div>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <script>
        const propertyTypeData = <?php echo json_encode($propertyTypeData); ?>;
        const monthlyData = <?php echo json_encode(['monthly' => $monthlyStats]); ?>;
        const monthlyReportData = <?php echo json_encode(['monthly' => $reportStats]); ?>;
        initializeReportStatsChart(monthlyReportData);
        console.log(propertyTypeData);
        initializeCharts(propertyTypeData);
        initializeReportsCharts(monthlyData);


        // Get data for each section
function getSectionData(section) {
    const data = {
        property: {
            title: 'Property Overview Report',
            data: [
                ['Metric', 'Value'],
                ['Total Properties', '<?php echo $totalProperties; ?>'],
                ['Active Rentals', '<?php echo $activeRentals; ?>'],
                ['Available Properties', '<?php echo $totalProperties - $activeRentals; ?>'],
                ['Occupancy Rate', '<?php echo round(($activeRentals/$totalProperties)*100, 1); ?>%']
            ]
        },
        client: {
            title: 'Client Analytics Report',
            data: [
                ['Metric', 'Value'],
                ['Total Clients', '<?php echo $registeredClients; ?>'],
                ['Pending Negotiations', '<?php echo $pendingNegotiations; ?>']
            ]
        },
        owner: {
            title: 'Property Owners Analytics Report',
            data: [
                ['Metric', 'Value'],
                ['Total Property Owners', '<?php echo $registeredPropertyOwners; ?>'],
                ['VIP Property Owners', '<?php echo $VIPPropertyOwners; ?>'],
                ['Regular Property Owners', '<?php echo $registeredPropertyOwners - $VIPPropertyOwners; ?>']
            ]
        }
    };
    return data[section];
}

// Export function
async function exportSection(section) {
    const button = event.target;
    const originalContent = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    button.disabled = true;
    
    try {
        const sectionData = getSectionData(section);
        await exportAsPDF(sectionData);
        
// Replace the alert with this:
// showToast('PDF exported successfully!', 'success');

// Add this function:

        
    } catch (error) {
        console.error('Export error:', error);
        // showToast('An error occurred while exporting the report.', 'error');
    } finally {
        // Reset button
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        }, 1000);
    }
}

// PDF Export
function exportAsPDF(sectionData) {
    return new Promise((resolve, reject) => {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Document dimensions
            const pageWidth = doc.internal.pageSize.width;
            const pageHeight = doc.internal.pageSize.height;
            const margin = 20;
            
            // Color palette
            const colors = {
                primary: '#2563eb',      // Blue
                secondary: '#64748b',    // Slate gray
                accent: '#f1f5f9',       // Light gray
                text: '#1e293b',         // Dark slate
                lightText: '#64748b'     // Medium gray
            };
            
            // Add header background
            doc.setFillColor(colors.accent);
            doc.rect(0, 0, pageWidth, 60, 'F');
            
            // Add logo (positioned in top-right corner)
            const logo = new Image();
            logo.onload = () => {
                doc.addImage(logo, 'PNG', pageWidth - 60, 10, 40, 40);
                generatePDFContent();
            };
            logo.onerror = () => {
                // Continue without logo if it fails to load
                generatePDFContent();
            };
            logo.src = '../rently2.png';
            
            function generatePDFContent() {
                // Company name/branding
                doc.setTextColor(colors.primary);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('RENTLY', margin, 25);
                
                // Main title
                doc.setTextColor(colors.text);
                doc.setFontSize(20);
                doc.setFont('helvetica', 'bold');
                doc.text(sectionData.title, margin, 45);
                
                // Subtitle line
                doc.setLineWidth(0.5);
                doc.setDrawColor(colors.primary);
                doc.line(margin, 50, pageWidth - margin, 50);
                
                // Date and metadata section
                doc.setTextColor(colors.lightText);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                const currentDate = new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                const currentTime = new Date().toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                doc.text(`Generated on: ${currentDate} at ${currentTime}`, margin, 65);
                doc.text(`Total Records: ${sectionData.data.length - 1}`, margin, 72); // -1 for header
                
                // Content section
                let yPosition = 90;
                
                // Check if we have data
                if (sectionData.data && sectionData.data.length > 0) {
                    // Table styling
                    const tableStartY = yPosition;
                    const rowHeight = 12;
                    const col1Width = 80;
                    const col2Width = pageWidth - margin * 2 - col1Width;
                    
                    sectionData.data.forEach((row, index) => {
                        // Check if we need a new page
                        if (yPosition > pageHeight - 40) {
                            doc.addPage();
                            yPosition = 30;
                        }
                        
                        // Header row styling
                        if (index === 0) {
                            // Header background
                            doc.setFillColor(colors.primary);
                            doc.rect(margin, yPosition - 8, pageWidth - margin * 2, rowHeight, 'F');
                            
                            // Header text
                            doc.setTextColor(255, 255, 255); // White text
                            doc.setFontSize(11);
                            doc.setFont('helvetica', 'bold');
                        } else {
                            // Alternate row backgrounds
                            if (index % 2 === 0) {
                                doc.setFillColor(colors.accent);
                                doc.rect(margin, yPosition - 8, pageWidth - margin * 2, rowHeight, 'F');
                            }
                            
                            // Data row text
                            doc.setTextColor(colors.text);
                            doc.setFontSize(10);
                            doc.setFont('helvetica', 'normal');
                        }
                        
                        // Add cell borders
                        doc.setDrawColor(colors.secondary);
                        doc.setLineWidth(0.1);
                        doc.rect(margin, yPosition - 8, col1Width, rowHeight);
                        doc.rect(margin + col1Width, yPosition - 8, col2Width, rowHeight);
                        
                        // Add text content with proper alignment
                        const text1 = row[0] ? row[0].toString() : '';
                        const text2 = row[1] ? row[1].toString() : '';
                        
                        // Truncate text if too long
                        const maxChars1 = 25;
                        const maxChars2 = 35;
                        const displayText1 = text1.length > maxChars1 ? text1.substring(0, maxChars1 - 3) + '...' : text1;
                        const displayText2 = text2.length > maxChars2 ? text2.substring(0, maxChars2 - 3) + '...' : text2;
                        
                        doc.text(displayText1, margin + 5, yPosition);
                        doc.text(displayText2, margin + col1Width + 5, yPosition);
                        
                        yPosition += rowHeight;
                    });
                    
                    // Add summary box if more than just header
                    if (sectionData.data.length > 1) {
                        yPosition += 10;
                        
                        // Summary section
                        doc.setFillColor(colors.accent);
                        doc.rect(margin, yPosition, pageWidth - margin * 2, 25, 'F');
                        doc.setDrawColor(colors.primary);
                        doc.setLineWidth(0.5);
                        doc.rect(margin, yPosition, pageWidth - margin * 2, 25);
                        
                        doc.setTextColor(colors.primary);
                        doc.setFontSize(12);
                        doc.setFont('helvetica', 'bold');
                        doc.text('Summary', margin + 5, yPosition + 10);
                        
                        doc.setTextColor(colors.text);
                        doc.setFontSize(9);
                        doc.setFont('helvetica', 'normal');
                        doc.text(`This report contains ${sectionData.data.length - 1} data entries.`, margin + 5, yPosition + 18);
                    }
                } else {
                    // No data message
                    doc.setTextColor(colors.lightText);
                    doc.setFontSize(12);
                    doc.setFont('helvetica', 'italic');
                    doc.text('No data available for this report.', margin, yPosition);
                }
                
                // Footer section
                const footerY = pageHeight - 20;
                
                // Footer line
                doc.setDrawColor(colors.primary);
                doc.setLineWidth(0.5);
                doc.line(margin, footerY - 5, pageWidth - margin, footerY - 5);
                
                // Footer text
                doc.setTextColor(colors.lightText);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text('Generated by Rently Platform', margin, footerY);
                
                // Page number
                doc.text(`Page ${doc.internal.getNumberOfPages()}`, pageWidth - margin - 20, footerY);
                
                // Website/contact info
                doc.text('www.rently.com | support@rently.com', pageWidth - margin - 80, footerY + 5);
                
                // Save the PDF
                const fileName = `${sectionData.title.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
                doc.save(fileName);
                
                resolve();
            }
            
            // If logo fails to load, generate content immediately
            setTimeout(() => {
                if (logo.complete === false) {
                    generatePDFContent();
                }
            }, 2000);
            
        } catch (error) {
            console.error('PDF generation failed:', error);
            reject(error);
        }
    });
}

// function showToast(message, type) {
//     const toast = document.createElement('div');
//     toast.className = `toast-notification toast-${type}`;
//     toast.innerHTML = `
//         <div class="toast-content">
//             <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
//             <span>${message}</span>
//         </div>
//         <button class="toast-close" onclick="this.parentElement.remove()">
//             <i class="fas fa-times"></i>
//         </button>
//     `;
    
//     document.body.appendChild(toast);
    
//     // Auto remove after 4 seconds
//     setTimeout(() => {
//         if (toast.parentElement) {
//             toast.classList.add('fade-out');
//             setTimeout(() => toast.remove(), 300);
//         }
//     }, 4000);
// }
    </script>
</body>

</html>