<?php
$page_title = "Fee Submission - Finchskills Institute";
include "header.php";
?>


<style>
@media (max-width: 767.98px) {
    .edufees-hero-section,
    .edufees-heading-wrap {
        display: none !important;
    }
    .edufees-main-section {
        padding: 16px 0 40px !important;
    }
    .edufees-main-wrapper {
        margin-top: 0 !important;
    }
    .edufees-form-box {
        padding: 22px 18px !important;
        border-radius: 12px !important;
    }
}
</style>

<section class="edufees-hero-section d-none d-md-block">
    <div class="container">
        <div class="edufees-hero-content">
            <div class="bloghero-breadcrumb">
                <a href="index.php" style="color: #fff;">Home</a>
                <span>
                    <i class="fa-solid fa-chevron-right" style="color: #fff;"></i>
                </span>
                <a href="fee-submission.php" style="color: #fff;">Fee Submission</a>

            </div>

        </div>
    </div>
</section>


<!-- =========================
     MAIN SECTION
========================= -->

<section class="edufees-main-section">

    <div class="container">

        <!-- HEADING -->

        <div class="edufees-heading-wrap d-none d-md-block">

            <h2>

                Welcome To
                <span>Finchskills Institute!</span>

            </h2>

            <p>
                Your journey starts here. Complete the fee submission process
                to confirm your admission successfully.
            </p>

        </div>

        <!-- MAIN WRAPPER -->

        <div class="edufees-main-wrapper">

            <div class="row g-4">

                <!-- LEFT -->

                <!--<div class="col-md-4">-->

                <!--    <div class="edufees-left-box">-->

                <!--        <h3>-->
                <!--            Next Step:-->
                <!--            Fee Submission-->
                <!--        </h3>-->

                <!--        <p>-->
                <!--            Scan the QR code below to complete your payment-->
                <!--            securely using any UPI app.-->
                <!--        </p>-->

                <!--         QR -->

                <!--        <div class="edufees-qr-box">-->

                <!--            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=UPIPayment" alt="QR Code">-->

                <!--        </div>-->

                        

                <!--        <div class="edufees-contact-box">-->

                <!--            <h5 class="text-center text-md-start">-->
                <!--                For Any Queries Contact Us-->
                <!--            </h5>-->

                <!--            <div class="edufees-contact-item">-->

                <!--                <i class="fa-solid fa-envelope"></i>-->

                <!--                <a href="#">-->
                <!--                    admission@finchskills.com-->
                <!--                </a>-->

                <!--            </div>-->

                <!--            <div class="edufees-contact-item">-->

                <!--                <i class="fa-solid fa-phone"></i>-->

                <!--                <span>-->
                <!--                    <?= htmlspecialchars($site_phone_1 ?? '') ?>-->
                <!--                </span>-->

                <!--            </div>-->

                <!--        </div>-->

                <!--    </div>-->

                <!--</div>-->

                <!-- RIGHT -->

                <div class="col-md-12">

                    <div class="edufees-form-box">

                        <h3>
                            Upload Payment Receipt
                        </h3>

                        <p>
                            After completing payment, upload your transaction details below.
                        </p>

                        <form action="send-fee.php" method="POST" enctype="multipart/form-data">

                            <div class="row">

                                <!-- NAME -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Full Name*
                                        </label>

                                        <input type="text"
                                            name="name"
                                            class="edufees-input"
                                            placeholder="Enter Full Name">

                                    </div>

                                </div>

                                <!-- CONTACT -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Contact Number*
                                        </label>

                                        <input type="text"
                                            name="contact"
                                            class="edufees-input"
                                            placeholder="Enter Contact Number">

                                    </div>

                                </div>

                                <!-- EMAIL -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Email Address*
                                        </label>

                                        <input type="email"
                                            name="email"
                                            class="edufees-input"
                                            placeholder="Enter Email Address">

                                    </div>

                                </div>

                                <!-- FEES TYPE -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Select Fee Type*
                                        </label>

                                        <select name="fee_type" class="edufees-select">

                                            <option>
                                                Select Fee Type
                                            </option>
                                            
                                            <option>
                                                Registration Fee
                                            </option>

                                            <option>
                                                Admission Fee
                                            </option>

                                            <option>
                                                Installment
                                            </option>

                                            <option>
                                                Total Fee
                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- PURPOSE -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Payment Purpose*
                                        </label>
                                        
                                        <select name="purpose" class="edufees-input">

                                            <option>
                                                Education
                                            </option>

                                        </select>

                                        <!--<input type="text"-->
                                        <!--    name="purpose"-->
                                        <!--    class="edufees-input"-->
                                        <!--    placeholder="Enter Payment Purpose">-->

                                    </div>

                                </div>

                                <!-- COURSE -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Course Applied*
                                        </label>
                                        
                                        <select name="course" class="edufees-select">

                                            <option value="">Select Course</option>
                                            <option>Foundation Course in Tourism</option>
                                            <option>Certificate Course in Travel & Air Ticketing</option>
                                            <option>Professional Course in Travel & Tourism</option>
                                            <option>Professional Course in Ground Staff & Hospitality</option>
                                            <option>Professional Course in Personality Development</option>
                                            <option>Professional Course in Airport Terminal Management</option>
                                            <option>Certificate Course in Customer Service</option>
                                            <option>Professional Course in Event Management</option>
                                            <option>Foundation Course in Hotel Management</option>
                                            <option>Professional Course in Air Hostess</option>
                                            <option>Professional Course in Cabin Crew</option>

                                        </select>

                                        <!--<input type="text"-->
                                        <!--    name="course"-->
                                        <!--    class="edufees-input"-->
                                        <!--    placeholder="Enter Course Name">-->

                                    </div>

                                </div>

                                <!-- RECEIPT -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Upload Receipt*
                                        </label>

                                        <input type="file"
                                            name="receipt"
                                            class="edufees-file">

                                    </div>

                                </div>

                                <!-- SIGN -->

                                <div class="col-md-6">

                                    <div class="edufees-input-wrap">

                                        <label class="edufees-label">
                                            Upload Signature*
                                        </label>

                                        <input type="file"
                                            name="signature"
                                            class="edufees-file">

                                    </div>

                                </div>

                            </div>

                            <!-- CHECK -->

                            <div class="edufees-check-wrap">

                                <input type="checkbox" id="terms">

                                <label for="terms">
                                    I confirm that the above details are correct
                                    and the payment has been successfully completed.
                                    I accept all
                                    <a href="terms-conditions.php">
                                        Terms & Conditions
                                    </a>
                                </label>

                            </div>

                            <!-- BUTTON -->

                            <button type="submit"
                                class="edufees-submit-btn">

                                Submit Fee Details

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- FOOTER  -->
<?php include "footer.php" ?>