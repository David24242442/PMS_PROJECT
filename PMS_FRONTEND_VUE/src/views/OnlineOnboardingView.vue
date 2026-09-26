<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-red-100 selection:text-red-700 pb-28 sm:pb-16">
    <!-- Top Melcom Red Header Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-xs">
      <div class="h-1.5 w-full bg-gradient-to-r from-red-600 via-red-500 to-amber-500"></div>
      <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        <!-- Logo & Title -->
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-red-600 flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-200 shrink-0">
            M
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-black tracking-wider uppercase text-red-600">Melcom Group</span>
              <span class="inline-block w-1 h-1 rounded-full bg-slate-300"></span>
              <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">HR Careers</span>
            </div>
            <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-tight m-0">
              Employee Self-Onboarding
            </h1>
          </div>
        </div>

        <!-- Draft Saved Chip & Reset -->
        <div class="flex items-center gap-2">
          <div v-if="draftSavedAt" class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Draft saved {{ draftSavedAt }}</span>
          </div>
          <button
            type="button"
            @click="promptClearDraft"
            title="Clear and start new form"
            class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-200 text-xs font-semibold transition flex items-center gap-1"
          >
            <i class="pi pi-refresh text-xs"></i>
            <span class="hidden sm:inline">Reset</span>
          </button>
        </div>
      </div>

      <!-- Step Progress Bar -->
      <div class="max-w-5xl mx-auto px-4 pt-2 pb-3">
        <div class="flex items-center justify-between text-xs font-bold text-slate-600 mb-2">
          <span class="text-red-600 uppercase tracking-wider font-extrabold text-[11px]">
            Step {{ currentStep }} of {{ totalSteps }}: {{ stepTitles[currentStep - 1] }}
          </span>
          <span class="text-slate-400 font-medium">{{ Math.round((currentStep / totalSteps) * 100) }}% Completed</span>
        </div>

        <!-- Progress Track -->
        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
          <div
            class="bg-gradient-to-r from-red-600 to-red-500 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
          ></div>
        </div>

        <!-- Step Pills / Navigation for Tablet & Desktop -->
        <div class="hidden sm:grid grid-cols-5 gap-2 mt-3 pt-1">
          <button
            v-for="(title, idx) in stepTitles"
            :key="idx"
            type="button"
            @click="goToStep(idx + 1)"
            class="flex items-center gap-2 py-1.5 px-2 rounded-lg text-left transition border text-xs"
            :class="[
              currentStep === idx + 1
                ? 'bg-red-50 border-red-200 text-red-700 font-bold shadow-xs'
                : currentStep > idx + 1
                ? 'bg-slate-50 border-slate-200 text-slate-700 font-semibold'
                : 'bg-white border-transparent text-slate-400'
            ]"
          >
            <span
              class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
              :class="[
                currentStep === idx + 1
                  ? 'bg-red-600 text-white'
                  : currentStep > idx + 1
                  ? 'bg-emerald-600 text-white'
                  : 'bg-slate-200 text-slate-600'
              ]"
            >
              <i v-if="currentStep > idx + 1" class="pi pi-check text-[9px]"></i>
              <span v-else>{{ idx + 1 }}</span>
            </span>
            <span class="truncate">{{ title }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-4 pt-6 pb-12">
      <!-- Success Screen -->
      <div v-if="submittedSuccess" class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-10 text-center animate-fade-in">
        <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-5 shadow-inner">
          <i class="pi pi-check text-4xl font-black"></i>
        </div>
        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-extrabold uppercase tracking-wider inline-block mb-3">
          Application Received
        </span>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2">
          Congratulations, {{ successData.candidate_name || emp.firstname }}!
        </h2>
        <p class="text-slate-600 max-w-lg mx-auto text-sm sm:text-base mb-6">
          Your Melcom employee self-onboarding application has been securely transmitted to the Melcom HR Department.
        </p>

        <!-- Reference Box -->
        <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl p-5 max-w-md mx-auto mb-8 text-left">
          <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200">
            <span class="text-xs uppercase font-bold text-slate-500">Tracking Reference</span>
            <button
              type="button"
              @click="copyReference(successData.reference_number)"
              class="text-xs text-red-600 hover:text-red-700 font-bold flex items-center gap-1"
            >
              <i class="pi pi-copy text-xs"></i>
              <span>{{ copyStatusText }}</span>
            </button>
          </div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono tracking-wider break-all mb-4 text-center py-1 bg-white rounded-lg border border-slate-200">
            {{ successData.reference_number || 'MEL-ONB-PENDING' }}
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Position:</span>
              <span class="font-bold text-slate-800">{{ emp.joiningposition || 'Melcom Staff' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Company / Branch:</span>
              <span class="font-bold text-slate-800">{{ getCompanyName(emp.joining_company_id) }} ({{ getBranchName(emp.joining_branch_id) }})</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Temporary Code:</span>
              <span class="font-mono font-bold text-red-600">{{ successData.employee_code || 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-slate-500 font-medium">Submitted At:</span>
              <span class="font-semibold text-slate-700">{{ successData.submission_date || new Date().toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <!-- What Happens Next Guide -->
        <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 max-w-lg mx-auto mb-8 text-left">
          <h3 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-2 mb-3">
            <i class="pi pi-info-circle text-indigo-600"></i>
            What Happens Next?
          </h3>
          <ol class="space-y-2.5 text-xs text-indigo-950 font-medium list-decimal list-inside">
            <li><strong>HR Verification:</strong> Melcom HR will review your uploaded documents and verify your Ghana Card.</li>
            <li><strong>Notification:</strong> You will receive a WhatsApp message or SMS once your employee profile is approved.</li>
            <li><strong>First Day Reporting:</strong> Remember to carry your original Ghana Card and certificates on your reporting date.</li>
          </ol>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <button
            type="button"
            @click="printSummary"
            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-900 text-white font-bold text-sm hover:bg-slate-800 transition flex items-center justify-center gap-2 shadow-sm"
          >
            <i class="pi pi-print"></i>
            Print / Save Confirmation (PDF)
          </button>
          <button
            type="button"
            @click="startNewApplication"
            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition"
          >
            Submit Another Application
          </button>
        </div>
      </div>

      <!-- Multi-step Form -->
      <form v-else @submit.prevent="handleNextOrSubmit" novalidate>
        <!-- Global Validation Error Banner -->
        <div v-if="validationErrors.length > 0" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs">
          <div class="flex items-center gap-2 font-bold mb-2">
            <i class="pi pi-exclamation-triangle text-red-600"></i>
            <span>Please complete the required fields to continue:</span>
          </div>
          <ul class="list-disc list-inside space-y-1 pl-1">
            <li v-for="(err, i) in validationErrors" :key="i">{{ err }}</li>
          </ul>
        </div>

        <!-- ========================================== -->
        <!-- STEP 1: POSITION DETAILS & PERSONAL INFO  -->
        <!-- ========================================== -->
        <div v-show="currentStep === 1" class="space-y-6 animate-fade-in">
          <!-- Card: Melcom Employee Position Details -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Melcom Employee Position Details
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Specify your hiring company, department, branch and contract role.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Joining Company -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Joining Company <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.joining_company_id"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option v-for="c in companyList" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>

              <!-- Contract Category -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Contract Category <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.contracttype"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option v-for="ct in contractTypes" :key="ct.code" :value="ct.code">{{ ct.name }}</option>
                </select>
              </div>

              <!-- Branch / Location -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Branch / Store Location <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.joining_branch_id"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option value="" disabled>-- Select Assigned Branch --</option>
                  <option v-for="b in branchList" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>

              <!-- Department -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Department <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.joining_dept_id"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option value="" disabled>-- Select Department --</option>
                  <option v-for="d in deptList" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>

              <!-- Joining Position / Job Title -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Job Title / Designation <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="emp.joiningposition"
                  placeholder="e.g. CASHIER, SALES EXECUTIVE, CLERK"
                  @input="emp.joiningposition = (emp.joiningposition || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Expected Joining Date -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Expected Start Date <span class="text-red-500">*</span></label>
                <input
                  type="date"
                  v-model="emp.joiningdate"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Card: Personal Information -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  Personal Information
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Fill out your official personal identity details exactly as shown on your Ghana Card.</p>
              </div>

              <!-- Profile Picture / Live Selfie Camera on Mobile -->
              <div class="flex items-center gap-3">
                <div class="relative w-16 h-16 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 overflow-hidden shrink-0 flex items-center justify-center shadow-inner">
                  <img
                    v-if="emp.profilepicture"
                    :src="emp.profilepicture"
                    alt="Passport Preview"
                    class="w-full h-full object-cover"
                  />
                  <i v-else class="pi pi-user text-2xl text-slate-400"></i>
                </div>
                <div>
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-bold hover:bg-red-100 transition">
                    <i class="pi pi-camera"></i>
                    <span>{{ emp.profilepicture ? 'Change Photo' : 'Take / Upload Selfie' }}</span>
                    <input
                      type="file"
                      accept="image/*"
                      capture="user"
                      @change="handleProfilePhoto"
                      class="hidden"
                    />
                  </label>
                  <p class="text-[11px] text-slate-400 mt-1">Clear passport photo or selfie</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- First Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="emp.firstname"
                  placeholder="e.g. KWAME"
                  @input="onNameChange"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Surname -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Surname (Last Name) <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="emp.surname"
                  placeholder="e.g. MENSAH"
                  @input="onNameChange"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Other Names -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Middle / Other Names</label>
                <input
                  type="text"
                  v-model="emp.othername"
                  placeholder="e.g. KOFI"
                  @input="emp.othername = (emp.othername || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Gender -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.gender"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option value="M">MALE</option>
                  <option value="F">FEMALE</option>
                </select>
              </div>

              <!-- Date of Birth & Live Age Calculation -->
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="text-xs font-bold text-slate-700">Date of Birth <span class="text-red-500">*</span></label>
                  <span v-if="computedAge !== null" class="text-[11px] font-extrabold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Age: {{ computedAge }} yrs
                  </span>
                </div>
                <input
                  type="date"
                  v-model="emp.dob"
                  :max="maxDobDate"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Ghana Card Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  Ghana Card Number <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="emp.ghcardno"
                  placeholder="e.g. GHA-123456789-0"
                  @input="formatGhCard"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-mono font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Ghana Card Expiry -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Ghana Card Expiry Date</label>
                <input
                  type="date"
                  v-model="emp.ghcardexpiry"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Mobile Phone Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mobile Phone (Primary) <span class="text-red-500">*</span></label>
                <input
                  type="tel"
                  v-model="emp.mobileno"
                  placeholder="e.g. 0244123456"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- WhatsApp Phone Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Number</label>
                <input
                  type="tel"
                  v-model="emp.whatsappno"
                  placeholder="e.g. 0550123456"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Email Address -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Personal Email Address</label>
                <input
                  type="email"
                  v-model="emp.email"
                  placeholder="e.g. name@example.com"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- SSNIT Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">SSNIT Number (If available)</label>
                <input
                  type="text"
                  v-model="emp.socialsecurityno"
                  placeholder="e.g. C012345678912"
                  @input="emp.socialsecurityno = (emp.socialsecurityno || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-mono uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Region -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Region of Residence <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.region_id"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option v-for="r in regionList" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>

              <!-- Hometown -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hometown</label>
                <input
                  type="text"
                  v-model="emp.hometown"
                  placeholder="e.g. KUMASI"
                  @input="emp.hometown = (emp.hometown || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Digital GPS Address -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">GhanaPost GPS Digital Address</label>
                <input
                  type="text"
                  v-model="emp.gpsaddress"
                  placeholder="e.g. GA-183-9024"
                  @input="emp.gpsaddress = (emp.gpsaddress || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-mono uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Residential Address -->
              <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Residential Address (Current Living Address) <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="emp.resaddress"
                  placeholder="e.g. Hse No. 12, Spintex Road, near Shell Station, Accra"
                  @input="emp.resaddress = (emp.resaddress || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Father's Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Father's Full Name</label>
                <input
                  type="text"
                  v-model="emp.fathersname"
                  placeholder="e.g. JOHN MENSAH SR."
                  @input="emp.fathersname = (emp.fathersname || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Mother's Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mother's Full Name</label>
                <input
                  type="text"
                  v-model="emp.mothersname"
                  placeholder="e.g. MARY MENSAH"
                  @input="emp.mothersname = (emp.mothersname || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- STEP 2: FAMILY, SPOUSE, CHILDREN, NOMINEE & EMERGENCY   -->
        <!-- ======================================================== -->
        <div v-show="currentStep === 2" class="space-y-6 animate-fade-in">
          <!-- Card: Family & Spouse Details -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Family & Spouse Details
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Marital status and spouse details for company records and welfare benefits.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Marital Status -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Marital Status <span class="text-red-500">*</span></label>
                <select
                  v-model="emp.maritalstatus"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option v-for="m in maritalList" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
              </div>

              <!-- Spouse Name (If married) -->
              <div v-if="emp.maritalstatus == 2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Spouse Full Name</label>
                <input
                  type="text"
                  v-model="spouse.name"
                  placeholder="e.g. GRACE MENSAH"
                  @input="spouse.name = (spouse.name || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Spouse Phone -->
              <div v-if="emp.maritalstatus == 2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Spouse Phone Number</label>
                <input
                  type="tel"
                  v-model="spouse.phoneno"
                  placeholder="e.g. 0244987654"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Spouse Occupation -->
              <div v-if="emp.maritalstatus == 2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Spouse Occupation</label>
                <input
                  type="text"
                  v-model="spouse.occupation"
                  placeholder="e.g. NURSE, TEACHER, TRADER"
                  @input="spouse.occupation = (spouse.occupation || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Spouse Address -->
              <div v-if="emp.maritalstatus == 2" class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Spouse Address</label>
                <input
                  type="text"
                  v-model="spouse.address"
                  placeholder="e.g. SAME AS RESIDENTIAL ADDRESS OR SPECIFY"
                  @input="spouse.address = (spouse.address || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>
            </div>
          </div>

          <!-- Card: Children Details -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  Children Details
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Dependent children for medical coverage and employee records.</p>
              </div>
              <button
                type="button"
                @click="addChild"
                class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-bold hover:bg-red-100 transition flex items-center gap-1.5"
              >
                <i class="pi pi-plus text-xs"></i>
                <span>Add Child</span>
              </button>
            </div>

            <div v-if="childrens.length === 0" class="p-6 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-500 font-medium">
              No children added. If you have dependent children, tap <strong>"Add Child"</strong> above.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(child, idx) in childrens"
                :key="idx"
                class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end"
              >
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Child {{ idx + 1 }} Full Name</label>
                  <input
                    type="text"
                    v-model="child.name"
                    placeholder="e.g. KOFI MENSAH"
                    @input="child.name = (child.name || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Gender</label>
                  <select
                    v-model="child.gender"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  >
                    <option value="M">MALE</option>
                    <option value="F">FEMALE</option>
                  </select>
                </div>
                <div class="flex items-center gap-2">
                  <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Date of Birth</label>
                    <input
                      type="date"
                      v-model="child.dob"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <button
                    type="button"
                    @click="removeChild(idx)"
                    class="h-10 w-10 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition flex items-center justify-center shrink-0 mt-5"
                    title="Remove child"
                  >
                    <i class="pi pi-trash text-xs"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Nominee Information (Next of Kin) -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Nominee Information <span class="font-normal text-slate-500 text-xs sm:text-sm">(Next of Kin)</span>
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Person legally designated as your next of kin for official and estate purposes.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Nominee Full Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nominee Full Name <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="nominee.name"
                  placeholder="e.g. MARY MENSAH"
                  @input="nominee.name = (nominee.name || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Relationship -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to You <span class="text-red-500">*</span></label>
                <select
                  v-model="nominee.relationship"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option value="MOTHER">MOTHER</option>
                  <option value="FATHER">FATHER</option>
                  <option value="SPOUSE">SPOUSE</option>
                  <option value="BROTHER">BROTHER</option>
                  <option value="SISTER">SISTER</option>
                  <option value="SON">SON</option>
                  <option value="DAUGHTER">DAUGHTER</option>
                  <option value="UNCLE">UNCLE</option>
                  <option value="AUNT">AUNT</option>
                  <option value="GUARDIAN">GUARDIAN</option>
                  <option value="OTHER">OTHER</option>
                </select>
              </div>

              <!-- Nominee Phone -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number <span class="text-red-500">*</span></label>
                <input
                  type="tel"
                  v-model="nominee.phoneno"
                  placeholder="e.g. 0244112233"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Nominee Address -->
              <div class="sm:col-span-2 lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Residential Address / Landmark</label>
                <input
                  type="text"
                  v-model="nominee.address"
                  placeholder="e.g. Hse No. 44, Dome Pillar 2, Accra"
                  @input="nominee.address = (nominee.address || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>
            </div>
          </div>

          <!-- Card: Emergency Contact Information [Relatives Only] -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Emergency Contact Information [Relatives Only]
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Direct blood relatives who can be contacted immediately in case of medical or workplace emergency.</p>
            </div>

            <!-- Contact 1 (Required) -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-4">
              <span class="text-xs font-extrabold uppercase text-slate-700 mb-3 block">Primary Emergency Contact (Required)</span>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Relative Full Name <span class="text-red-500">*</span></label>
                  <input
                    type="text"
                    v-model="econts[0].name"
                    placeholder="e.g. KWAME MENSAH"
                    @input="econts[0].name = (econts[0].name || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    required
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Relationship <span class="text-red-500">*</span></label>
                  <input
                    type="text"
                    v-model="econts[0].relationship"
                    placeholder="e.g. BROTHER, MOTHER, UNCLE"
                    @input="econts[0].relationship = (econts[0].relationship || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    required
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Mobile Phone <span class="text-red-500">*</span></label>
                  <input
                    type="tel"
                    v-model="econts[0].phoneno"
                    placeholder="e.g. 0244001122"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    required
                  />
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Residential Address</label>
                  <input
                    type="text"
                    v-model="econts[0].address"
                    placeholder="e.g. Achimota Mile 7, Accra"
                    @input="econts[0].address = (econts[0].address || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  />
                </div>
              </div>
            </div>

            <!-- Contact 2 (Optional) -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-xs font-bold uppercase text-slate-600 mb-3 block">Secondary Emergency Contact (Optional)</span>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-medium text-slate-700 mb-1">Relative Full Name</label>
                  <input
                    type="text"
                    v-model="econts[1].name"
                    placeholder="e.g. KOFI OSEI"
                    @input="econts[1].name = (econts[1].name || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-700 mb-1">Relationship</label>
                  <input
                    type="text"
                    v-model="econts[1].relationship"
                    placeholder="e.g. SISTER, COUSIN"
                    @input="econts[1].relationship = (econts[1].relationship || '').toUpperCase()"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-700 mb-1">Mobile Phone</label>
                  <input
                    type="tel"
                    v-model="econts[1].phoneno"
                    placeholder="e.g. 0500112233"
                    class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- STEP 3: EDUCATION, WORK EXPERIENCE & REFERENCES          -->
        <!-- ======================================================== -->
        <div v-show="currentStep === 3" class="space-y-6 animate-fade-in">
          <!-- Card: Educational Background -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  Educational Background
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">List your schools attended, qualifications, and certificates.</p>
              </div>
              <button
                type="button"
                @click="addEducation"
                class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-bold hover:bg-red-100 transition flex items-center gap-1.5"
              >
                <i class="pi pi-plus text-xs"></i>
                <span>Add School</span>
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(edu, idx) in edus"
                :key="idx"
                class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative"
              >
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-extrabold uppercase text-slate-700">Education Entry #{{ idx + 1 }}</span>
                  <button
                    v-if="edus.length > 1"
                    type="button"
                    @click="removeEducation(idx)"
                    class="text-xs text-red-600 hover:text-red-700 font-bold flex items-center gap-1"
                  >
                    <i class="pi pi-trash text-xs"></i>
                    <span>Remove</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                  <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">School / Institution Name <span class="text-red-500">*</span></label>
                    <input
                      type="text"
                      v-model="edu.schoolname"
                      placeholder="e.g. ACCRA TECHNICAL UNIVERSITY / ACCRA ACADEMY"
                      @input="edu.schoolname = (edu.schoolname || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Qualification Attained <span class="text-red-500">*</span></label>
                    <select
                      v-model="edu.qualification"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                      required
                    >
                      <option value="WASSCE / SSCE">WASSCE / SSCE</option>
                      <option value="BECE">BECE</option>
                      <option value="HND">HND (DIPLOMA)</option>
                      <option value="BACHELOR DEGREE">BACHELOR'S DEGREE</option>
                      <option value="MASTERS DEGREE">MASTER'S DEGREE</option>
                      <option value="NVTI / VOCATIONAL">NVTI / VOCATIONAL</option>
                      <option value="PROFESSIONAL CERT">PROFESSIONAL CERTIFICATE</option>
                      <option value="OTHER">OTHER</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Course / Programme</label>
                    <input
                      type="text"
                      v-model="edu.coursetitle"
                      placeholder="e.g. GENERAL ARTS, ACCOUNTING"
                      @input="edu.coursetitle = (edu.coursetitle || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Start Year</label>
                    <input
                      type="date"
                      v-model="edu.fromdate"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">End / Completion Year</label>
                    <input
                      type="date"
                      v-model="edu.todate"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: WORK EXPERIENCE -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  WORK EXPERIENCE
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Previous employers, roles held, and work history.</p>
              </div>
              <button
                type="button"
                @click="addWorkExp"
                class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-bold hover:bg-red-100 transition flex items-center gap-1.5"
              >
                <i class="pi pi-plus text-xs"></i>
                <span>Add Experience</span>
              </button>
            </div>

            <div v-if="workexps.length === 0" class="p-6 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-500 font-medium">
              No previous work history added. If this is your first job, you may proceed, or tap <strong>"Add Experience"</strong> if you have previous roles.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(w, idx) in workexps"
                :key="idx"
                class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative"
              >
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-extrabold uppercase text-slate-700">Work Experience #{{ idx + 1 }}</span>
                  <button
                    type="button"
                    @click="removeWorkExp(idx)"
                    class="text-xs text-red-600 hover:text-red-700 font-bold flex items-center gap-1"
                  >
                    <i class="pi pi-trash text-xs"></i>
                    <span>Remove</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Employer Name</label>
                    <input
                      type="text"
                      v-model="w.companyname"
                      placeholder="e.g. SHOPRITE / ABC LOGISTICS"
                      @input="w.companyname = (w.companyname || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Position / Job Title</label>
                    <input
                      type="text"
                      v-model="w.jobtitle"
                      placeholder="e.g. CASHIER / SALES ASSISTANT"
                      @input="w.jobtitle = (w.jobtitle || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Leaving</label>
                    <input
                      type="text"
                      v-model="w.reasonforleaving"
                      placeholder="e.g. Career growth / Relocation"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">From Date</label>
                    <input
                      type="date"
                      v-model="w.fromdate"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">To Date</label>
                    <input
                      type="date"
                      v-model="w.todate"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Last Drawn Salary (Optional)</label>
                    <input
                      type="text"
                      v-model="w.salary"
                      placeholder="e.g. GHS 1,500"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: REFERENCES -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  REFERENCES
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Provide at least 1 or 2 reputable persons who can confirm your character and conduct.</p>
              </div>
              <button
                type="button"
                @click="addReference"
                class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 border border-red-200 text-xs font-bold hover:bg-red-100 transition flex items-center gap-1.5"
              >
                <i class="pi pi-plus text-xs"></i>
                <span>Add Reference</span>
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(ref, idx) in refs"
                :key="idx"
                class="p-4 rounded-xl bg-slate-50 border border-slate-200"
              >
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-extrabold uppercase text-slate-700">Referee #{{ idx + 1 }}</span>
                  <button
                    v-if="refs.length > 1"
                    type="button"
                    @click="removeReference(idx)"
                    class="text-xs text-red-600 hover:text-red-700 font-bold flex items-center gap-1"
                  >
                    <i class="pi pi-trash text-xs"></i>
                    <span>Remove</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input
                      type="text"
                      v-model="ref.name"
                      placeholder="e.g. REV. DANIEL APPIAH"
                      @input="ref.name = (ref.name || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Position / Profession</label>
                    <input
                      type="text"
                      v-model="ref.position"
                      placeholder="e.g. HEADMASTER / MANAGER"
                      @input="ref.position = (ref.position || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Organization</label>
                    <input
                      type="text"
                      v-model="ref.organization"
                      placeholder="e.g. GES / METHODIST CHURCH"
                      @input="ref.organization = (ref.organization || '').toUpperCase()"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input
                      type="tel"
                      v-model="ref.phoneno"
                      placeholder="e.g. 0244778899"
                      class="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-xs font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- =============================================================== -->
        <!-- STEP 4: BANK DETAILS, GUARANTOR & IRREVOCABLE GUARANTEE        -->
        <!-- =============================================================== -->
        <div v-show="currentStep === 4" class="space-y-6 animate-fade-in">
          <!-- Card: Bank & Social Security Information -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Bank & Social Security Information
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Official bank account for monthly salary direct credit by Melcom Group.</p>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900 text-xs font-medium mb-5 flex items-start gap-2.5">
              <i class="pi pi-info-circle text-amber-600 text-base shrink-0 mt-0.5"></i>
              <span><strong>Important:</strong> The bank account name must match your legal name on your Ghana Card. Third-party accounts cannot be accepted.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Bank Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Bank Name <span class="text-red-500">*</span></label>
                <select
                  v-model="banksocial.bankname"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option value="" disabled>-- Select Your Bank --</option>
                  <option v-for="b in bankList" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>

              <!-- Bank Branch -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Bank Branch <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="banksocial.bankbranch"
                  placeholder="e.g. SPINTEX ROAD BRANCH"
                  @input="banksocial.bankbranch = (banksocial.bankbranch || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Account Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Name <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="banksocial.accountname"
                  placeholder="e.g. KWAME MENSAH"
                  @input="banksocial.accountname = (banksocial.accountname || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Account Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Number <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="banksocial.accountnumber"
                  placeholder="e.g. 1011234567890"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-mono font-bold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Account Type -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Type <span class="text-red-500">*</span></label>
                <select
                  v-model="banksocial.accounttype"
                  class="w-full h-11 px-3 rounded-xl border border-slate-300 bg-white text-sm font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                >
                  <option :value="1">CURRENT ACCOUNT</option>
                  <option :value="2">SAVINGS ACCOUNT</option>
                </select>
              </div>

              <!-- Tier 2 Pension Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tier 2 Pension Number (Petra Trust)</label>
                <input
                  type="text"
                  v-model="banksocial.petratrustnumber"
                  placeholder="e.g. PT-123456"
                  @input="banksocial.petratrustnumber = (banksocial.petratrustnumber || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-mono uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>
            </div>
          </div>

          <!-- Card: Guarantor Details -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Guarantor Details
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Person standing as guarantor and surety for candidate's integrity at Melcom Group.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Guarantor Full Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guarantor Full Name <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="guarantor.name"
                  placeholder="e.g. DR. EMMANUEL ADDO"
                  @input="guarantor.name = (guarantor.name || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Relationship -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Relationship to Candidate <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  v-model="guarantor.relation"
                  placeholder="e.g. UNCLE, PASTOR, SENIOR COLLEAGUE"
                  @input="guarantor.relation = (guarantor.relation || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Guarantor Phone Number -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guarantor Mobile Phone <span class="text-red-500">*</span></label>
                <input
                  type="tel"
                  v-model="guarantor.phoneno"
                  placeholder="e.g. 0244123890"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                  required
                />
              </div>

              <!-- Guarantor Occupation -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Occupation / Profession</label>
                <input
                  type="text"
                  v-model="guarantor.occupation"
                  placeholder="e.g. MEDICAL OFFICER / ACCOUNTANT"
                  @input="guarantor.occupation = (guarantor.occupation || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Guarantor Employer -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Employer / Workplace</label>
                <input
                  type="text"
                  v-model="guarantor.employer"
                  placeholder="e.g. KORLE BU TEACHING HOSPITAL"
                  @input="guarantor.employer = (guarantor.employer || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Guarantor Ghana Card -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guarantor Ghana Card No.</label>
                <input
                  type="text"
                  v-model="guarantor.ghcardno"
                  placeholder="e.g. GHA-987654321-0"
                  @input="guarantor.ghcardno = (guarantor.ghcardno || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm font-mono uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>

              <!-- Guarantor Address -->
              <div class="sm:col-span-2 lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guarantor Residential Address / Location</label>
                <input
                  type="text"
                  v-model="guarantor.address"
                  placeholder="e.g. Plot 15, East Legon Hills, Accra"
                  @input="guarantor.address = (guarantor.address || '').toUpperCase()"
                  class="w-full h-11 px-3.5 rounded-xl border border-slate-300 bg-white text-sm uppercase font-medium focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                />
              </div>
            </div>
          </div>

          <!-- Card: Irrevocable Guarantee -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-4">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Irrevocable Guarantee
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Guarantor commitment terms for employee accountability.</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed space-y-2">
              <p>
                In consideration of Melcom Group offering employment to <strong>{{ emp.firstname || 'the Candidate' }} {{ emp.surname }}</strong>, the guarantor agrees to stand as surety and guarantee the honest, faithful, and diligent performance of the candidate's duties.
              </p>
              <div class="flex items-center gap-3 pt-2">
                <label class="font-bold text-slate-800">Guarantee Indemnity Amount:</label>
                <span class="font-extrabold font-mono text-red-600 bg-red-50 px-2.5 py-1 rounded-md border border-red-200">GHS 5,000.00</span>
              </div>
            </div>
          </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 5: DOCUMENT UPLOADS, DIGITAL SIGNATURE & DECLARATION           -->
        <!-- =================================================================== -->
        <div v-show="currentStep === 5" class="space-y-6 animate-fade-in">
          <!-- Card: Required Document Uploads -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                Required Document Uploads
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-1">Take a photo using your phone camera or upload digital copies of your official documents.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Upload 1: Ghana Card Front -->
              <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-red-300 transition">
                <span class="text-xs font-extrabold uppercase text-slate-800 block mb-2">Ghana Card (Front Side) <span class="text-red-500">*</span></span>
                <div v-if="documents.ghcard_front" class="relative mb-3">
                  <img :src="documents.ghcard_front" alt="Ghana Card Front" class="max-h-36 mx-auto rounded-lg shadow-sm border border-slate-200 object-cover" />
                  <button
                    type="button"
                    @click="documents.ghcard_front = null"
                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 text-[10px] hover:bg-red-700 shadow"
                    title="Remove"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>
                <div v-else class="py-4">
                  <i class="pi pi-id-card text-3xl text-slate-400 mb-2 block"></i>
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition shadow-sm">
                    <i class="pi pi-camera"></i>
                    <span>Snap / Upload Front</span>
                    <input type="file" accept="image/*" @change="e => handleDocUpload(e, 'ghcard_front')" class="hidden" />
                  </label>
                  <p class="text-[11px] text-slate-400 mt-2">Clear photo of Ghana Card front</p>
                </div>
              </div>

              <!-- Upload 2: Ghana Card Back -->
              <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-red-300 transition">
                <span class="text-xs font-extrabold uppercase text-slate-800 block mb-2">Ghana Card (Back Side)</span>
                <div v-if="documents.ghcard_back" class="relative mb-3">
                  <img :src="documents.ghcard_back" alt="Ghana Card Back" class="max-h-36 mx-auto rounded-lg shadow-sm border border-slate-200 object-cover" />
                  <button
                    type="button"
                    @click="documents.ghcard_back = null"
                    class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 text-[10px] hover:bg-red-700 shadow"
                    title="Remove"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>
                <div v-else class="py-4">
                  <i class="pi pi-id-card text-3xl text-slate-400 mb-2 block"></i>
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition shadow-sm">
                    <i class="pi pi-camera"></i>
                    <span>Snap / Upload Back</span>
                    <input type="file" accept="image/*" @change="e => handleDocUpload(e, 'ghcard_back')" class="hidden" />
                  </label>
                  <p class="text-[11px] text-slate-400 mt-2">Back side barcode and signature</p>
                </div>
              </div>

              <!-- Upload 3: Resume / CV -->
              <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-red-300 transition">
                <span class="text-xs font-extrabold uppercase text-slate-800 block mb-2">Curriculum Vitae (CV / Resume)</span>
                <div v-if="documents.resume_cv" class="relative mb-3 flex items-center justify-center gap-2 py-3 bg-white rounded-lg border border-slate-200">
                  <i class="pi pi-file-pdf text-2xl text-red-600"></i>
                  <span class="text-xs font-bold text-slate-700">CV Document Attached</span>
                  <button
                    type="button"
                    @click="documents.resume_cv = null"
                    class="ml-2 text-red-600 hover:text-red-700 font-bold text-xs"
                  >
                    Remove
                  </button>
                </div>
                <div v-else class="py-4">
                  <i class="pi pi-file text-3xl text-slate-400 mb-2 block"></i>
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition shadow-sm">
                    <i class="pi pi-upload"></i>
                    <span>Upload CV (PDF or Image)</span>
                    <input type="file" accept="application/pdf,image/*" @change="e => handleDocUpload(e, 'resume_cv')" class="hidden" />
                  </label>
                  <p class="text-[11px] text-slate-400 mt-2">Optional if not available right now</p>
                </div>
              </div>

              <!-- Upload 4: Certificate -->
              <div class="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center relative hover:border-red-300 transition">
                <span class="text-xs font-extrabold uppercase text-slate-800 block mb-2">Highest Educational Certificate</span>
                <div v-if="documents.certificate" class="relative mb-3 flex items-center justify-center gap-2 py-3 bg-white rounded-lg border border-slate-200">
                  <i class="pi pi-file-check text-2xl text-emerald-600"></i>
                  <span class="text-xs font-bold text-slate-700">Certificate Attached</span>
                  <button
                    type="button"
                    @click="documents.certificate = null"
                    class="ml-2 text-red-600 hover:text-red-700 font-bold text-xs"
                  >
                    Remove
                  </button>
                </div>
                <div v-else class="py-4">
                  <i class="pi pi-book text-3xl text-slate-400 mb-2 block"></i>
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition shadow-sm">
                    <i class="pi pi-upload"></i>
                    <span>Upload Certificate</span>
                    <input type="file" accept="application/pdf,image/*" @change="e => handleDocUpload(e, 'certificate')" class="hidden" />
                  </label>
                  <p class="text-[11px] text-slate-400 mt-2">WASSCE, Degree, Diploma, etc.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Touch Digital Signature Pad -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-600"></span>
                  Touch Digital Signature <span class="text-red-500">*</span>
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Sign with your finger (mobile) or mouse cursor (desktop) inside the box below.</p>
              </div>
              <button
                type="button"
                @click="clearSignature"
                class="px-3 py-1.5 rounded-xl border border-slate-300 text-slate-600 hover:text-red-600 hover:border-red-200 text-xs font-bold transition flex items-center gap-1.5"
              >
                <i class="pi pi-refresh text-xs"></i>
                <span>Clear</span>
              </button>
            </div>

            <!-- Signature Canvas -->
            <div class="relative bg-slate-50 rounded-2xl border-2 border-slate-300 overflow-hidden touch-none">
              <canvas
                ref="sigCanvas"
                class="w-full h-44 sm:h-52 bg-white cursor-crosshair block"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart.prevent="handleTouchStart"
                @touchmove.prevent="handleTouchMove"
                @touchend.prevent="handleTouchEnd"
              ></canvas>

              <!-- Subtle baseline watermark -->
              <div class="absolute bottom-6 left-6 right-6 border-b border-dashed border-slate-200 pointer-events-none flex justify-between text-[10px] text-slate-400 font-medium pb-1">
                <span>Sign above this line</span>
                <span>Touch / Finger Canvas</span>
              </div>

              <!-- Signed Indicator -->
              <div v-if="hasSignature" class="absolute top-3 right-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-extrabold px-2.5 py-1 rounded-full flex items-center gap-1 shadow-xs pointer-events-none">
                <i class="pi pi-check text-[10px]"></i>
                <span>Signature Captured</span>
              </div>
            </div>
          </div>

          <!-- Card: EMPLOYEE'S DECLARATION -->
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7">
            <div class="border-b border-slate-100 pb-3 mb-4">
              <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                EMPLOYEE'S DECLARATION <span class="text-red-500">*</span>
              </h2>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed mb-5">
              <p class="font-medium">
                I hereby solemnly declare and certify that all details, certificates, credentials, and answers supplied by me in this onboarding application are true, accurate, and complete. I understand and agree that any willful misrepresentation, falsification, or suppression of material facts will disqualify me from employment or subject me to immediate summary termination of employment without notice, in line with Melcom Group policy and the Ghana Labour Act.
              </p>
            </div>

            <label class="flex items-start gap-3 cursor-pointer select-none p-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
              <input
                type="checkbox"
                v-model="agreedDeclaration"
                class="mt-0.5 h-5 w-5 rounded text-red-600 focus:ring-red-500 border-slate-300 cursor-pointer"
                required
              />
              <span class="text-xs font-bold text-slate-800 leading-snug">
                I have read, understood, and accept the Melcom Employee Declaration and affirm the truthfulness of my application. <span class="text-red-500">*</span>
              </span>
            </label>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM NAVIGATION / SUBMIT (DESKTOP)       -->
        <!-- ========================================== -->
        <div class="hidden sm:flex items-center justify-between pt-6 border-t border-slate-200 mt-6">
          <button
            v-if="currentStep > 1"
            type="button"
            @click="prevStep"
            class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition flex items-center gap-2"
          >
            <i class="pi pi-arrow-left text-xs"></i>
            <span>Previous Step</span>
          </button>
          <div v-else></div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="saveDraftToStorage(true)"
              class="px-4 py-3 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 font-semibold text-sm transition"
            >
              Save Draft
            </button>

            <button
              v-if="currentStep < totalSteps"
              type="button"
              @click="nextStep"
              class="px-8 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-sm transition flex items-center gap-2 shadow-lg shadow-red-200"
            >
              <span>Continue</span>
              <i class="pi pi-arrow-right text-xs"></i>
            </button>

            <button
              v-else
              type="submit"
              :disabled="submitting"
              class="px-8 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-sm transition flex items-center gap-2 shadow-xl shadow-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i v-if="submitting" class="pi pi-spin pi-spinner text-sm"></i>
              <i v-else class="pi pi-send text-sm"></i>
              <span>{{ submitting ? 'Submitting to HR...' : 'Submit Application' }}</span>
            </button>
          </div>
        </div>
      </form>
    </main>

    <!-- ========================================== -->
    <!-- STICKY MOBILE BOTTOM BAR (PHONE-FIRST UX)  -->
    <!-- ========================================== -->
    <div v-if="!submittedSuccess" class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-slate-200 p-3 shadow-2xl">
      <div class="flex items-center justify-between gap-2 max-w-lg mx-auto">
        <button
          v-if="currentStep > 1"
          type="button"
          @click="prevStep"
          class="h-12 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 active:bg-slate-100"
        >
          <i class="pi pi-arrow-left text-xs"></i>
          <span>Back</span>
        </button>
        <button
          v-else
          type="button"
          @click="saveDraftToStorage(true)"
          class="h-12 px-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs active:bg-slate-100"
        >
          Save
        </button>

        <div class="text-center px-1">
          <span class="text-[11px] font-extrabold text-slate-500 block">Step {{ currentStep }}/{{ totalSteps }}</span>
          <span class="text-[10px] text-slate-400 font-medium truncate block max-w-[120px]">{{ stepTitles[currentStep - 1] }}</span>
        </div>

        <button
          v-if="currentStep < totalSteps"
          type="button"
          @click="nextStep"
          class="h-12 px-6 rounded-xl bg-red-600 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-md shadow-red-200 active:bg-red-700"
        >
          <span>Next</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </button>

        <button
          v-else
          type="button"
          @click="handleNextOrSubmit"
          :disabled="submitting"
          class="h-12 px-6 rounded-xl bg-red-600 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-lg shadow-red-300 disabled:opacity-50"
        >
          <i v-if="submitting" class="pi pi-spin pi-spinner text-xs"></i>
          <span>{{ submitting ? 'Submitting...' : 'Submit Form' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import {
  companies as masterCompanies,
  depts as masterDepts,
  branchs as masterBranches,
  regions as masterRegions,
  banks as masterBanks,
  conttypes as masterConttypes,
  mstatus as masterMstatus
} from '@/data/masterdata'

// Master data lists
const companyList = ref(masterCompanies || [])
const deptList = ref(masterDepts || [])
const branchList = ref(masterBranches || [])
const regionList = ref(masterRegions || [])
const bankList = ref(masterBanks || [])
const contractTypes = ref(masterConttypes || [
  { code: 'contract', name: 'Contract' },
  { code: 'permanent', name: 'Permanent' },
  { code: 'probation', name: 'Probationary' },
  { code: 'casual', name: 'Casual' },
  { code: 'expat', name: 'Expat' }
])
const maritalList = ref(masterMstatus || [
  { id: 5, name: 'SINGLE' },
  { id: 2, name: 'MARRIED' },
  { id: 1, name: 'DIVORCED' },
  { id: 4, name: 'OTHERS' },
  { id: 3, name: 'NOT DISCLOSED' }
])

// Stepper
const currentStep = ref(1)
const totalSteps = 5
const stepTitles = [
  'Position & Personal',
  'Family & Contacts',
  'Education & Work',
  'Bank & Guarantor',
  'Docs & Declaration'
]

// Form State
const emp = reactive({
  joining_company_id: 3, // Melcom default
  contracttype: 'contract',
  joining_branch_id: 1,
  joining_dept_id: 34, // Retail
  joiningposition: '',
  joiningdate: new Date().toISOString().slice(0, 10),
  firstname: '',
  surname: '',
  othername: '',
  gender: 'M',
  dob: '',
  ghcardno: '',
  ghcardexpiry: '',
  mobileno: '',
  whatsappno: '',
  email: '',
  socialsecurityno: '',
  region_id: 7, // Greater Accra default
  hometown: '',
  gpsaddress: '',
  resaddress: '',
  fathersname: '',
  mothersname: '',
  maritalstatus: 5, // Single
  profilepicture: null,
  signature: null
})

const spouse = reactive({
  name: '',
  phoneno: '',
  occupation: '',
  address: ''
})

const childrens = ref([])

const nominee = reactive({
  name: '',
  relationship: 'MOTHER',
  phoneno: '',
  address: ''
})

const econts = ref([
  { name: '', relationship: 'MOTHER', phoneno: '', altphoneno: '', address: '' },
  { name: '', relationship: '', phoneno: '', altphoneno: '', address: '' }
])

const edus = ref([
  { schoolname: '', qualification: 'WASSCE / SSCE', coursetitle: '', fromdate: '', todate: '' }
])

const workexps = ref([])

const refs = ref([
  { name: '', position: '', organization: '', phoneno: '', email: '' }
])

const banksocial = reactive({
  bankname: '',
  bankbranch: '',
  accountname: '',
  accountnumber: '',
  accounttype: 1, // Current
  petratrustnumber: ''
})

const guarantor = reactive({
  name: '',
  relation: '',
  phoneno: '',
  occupation: '',
  employer: '',
  ghcardno: '',
  address: ''
})

const documents = reactive({
  ghcard_front: null,
  ghcard_back: null,
  resume_cv: null,
  certificate: null
})

const agreedDeclaration = ref(false)
const submitting = ref(false)
const submittedSuccess = ref(false)
const successData = reactive({
  reference_number: '',
  employee_code: '',
  candidate_name: '',
  position: '',
  submission_date: ''
})
const validationErrors = ref([])
const draftSavedAt = ref('')
const copyStatusText = ref('Copy')

// Canvas Signature
const sigCanvas = ref(null)
const isDrawing = ref(false)
const hasSignature = ref(false)
let canvasCtx = null

// Live Age Calculation
const computedAge = computed(() => {
  if (!emp.dob) return null
  const birth = new Date(emp.dob)
  const now = new Date()
  if (isNaN(birth.getTime())) return null
  let age = now.getFullYear() - birth.getFullYear()
  const m = now.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) {
    age--
  }
  return age >= 0 ? age : null
})

// DOB Constraints (must be at least 15 years old)
const maxDobDate = computed(() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() - 15)
  return d.toISOString().slice(0, 10)
})

// Auto sync Full Name to Account Name
const onNameChange = () => {
  emp.firstname = (emp.firstname || '').toUpperCase()
  emp.surname = (emp.surname || '').toUpperCase()
  if (!banksocial.accountname || banksocial.accountname === `${emp.firstname} ${emp.surname}`.trim()) {
    banksocial.accountname = `${emp.firstname} ${emp.surname}`.trim()
  }
}

// Ghana Card formatter: GHA-XXXXXXXXX-X
const formatGhCard = (e) => {
  let val = (emp.ghcardno || '').toUpperCase().replace(/[^A-Z0-9]/g, '')
  if (val.startsWith('GHA')) {
    val = val.substring(3)
  }
  let formatted = 'GHA-'
  if (val.length > 0) {
    formatted += val.substring(0, 9)
  }
  if (val.length > 9) {
    formatted += '-' + val.substring(9, 10)
  }
  emp.ghcardno = formatted
}

// Name lookup helpers
const getCompanyName = (id) => {
  const c = companyList.value.find(item => item.id === Number(id))
  return c ? c.name : 'Melcom'
}

const getBranchName = (id) => {
  const b = branchList.value.find(item => item.id === Number(id))
  return b ? b.name : 'Melcom Store'
}

// Add/Remove dynamic lists
const addChild = () => {
  childrens.value.push({ name: '', gender: 'M', dob: '' })
}
const removeChild = (index) => {
  childrens.value.splice(index, 1)
}

const addEducation = () => {
  edus.value.push({ schoolname: '', qualification: 'WASSCE / SSCE', coursetitle: '', fromdate: '', todate: '' })
}
const removeEducation = (index) => {
  edus.value.splice(index, 1)
}

const addWorkExp = () => {
  workexps.value.push({ companyname: '', jobtitle: '', reasonforleaving: '', fromdate: '', todate: '', salary: '' })
}
const removeWorkExp = (index) => {
  workexps.value.splice(index, 1)
}

const addReference = () => {
  refs.value.push({ name: '', position: '', organization: '', phoneno: '', email: '' })
}
const removeReference = (index) => {
  refs.value.splice(index, 1)
}

// Image & Document Handlers (Base64)
const handleProfilePhoto = (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    emp.profilepicture = event.target.result
    saveDraftToStorage()
  }
  reader.readAsDataURL(file)
}

const handleDocUpload = (e, field) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    documents[field] = event.target.result
    saveDraftToStorage()
  }
  reader.readAsDataURL(file)
}

// Step Navigation & Validation
const goToStep = (step) => {
  if (step > currentStep.value) {
    if (validateStep(currentStep.value)) {
      currentStep.value = step
      window.scrollTo({ top: 0, behavior: 'smooth' })
      if (step === 5) nextTick(initCanvas)
    }
  } else {
    currentStep.value = step
    window.scrollTo({ top: 0, behavior: 'smooth' })
    if (step === 5) nextTick(initCanvas)
  }
}

const nextStep = () => {
  if (validateStep(currentStep.value)) {
    if (currentStep.value < totalSteps) {
      currentStep.value++
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
      if (currentStep.value === 5) nextTick(initCanvas)
    }
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
    saveDraftToStorage()
  }
}

const handleNextOrSubmit = () => {
  if (currentStep.value < totalSteps) {
    nextStep()
  } else {
    submitApplication()
  }
}

// Step Validation Rules
const validateStep = (step) => {
  validationErrors.value = []
  const errors = []

  if (step === 1) {
    if (!emp.joining_company_id) errors.push('Please select a Joining Company.')
    if (!emp.joining_branch_id) errors.push('Please select your assigned Melcom Branch.')
    if (!emp.joining_dept_id) errors.push('Please select your Department.')
    if (!emp.joiningposition || !emp.joiningposition.trim()) errors.push('Please enter your Job Title / Position.')
    if (!emp.firstname || !emp.firstname.trim()) errors.push('Please enter your First Name.')
    if (!emp.surname || !emp.surname.trim()) errors.push('Please enter your Surname.')
    if (!emp.dob) errors.push('Please select your Date of Birth.')
    if (!emp.ghcardno || emp.ghcardno.length < 10) errors.push('Please enter a valid Ghana Card Number (e.g. GHA-123456789-0).')
    if (!emp.mobileno || emp.mobileno.length < 9) errors.push('Please enter a valid Primary Mobile Phone Number.')
    if (!emp.resaddress || !emp.resaddress.trim()) errors.push('Please enter your Residential Address.')
  } else if (step === 2) {
    if (!nominee.name || !nominee.name.trim()) errors.push('Please enter your Nominee (Next of Kin) Full Name.')
    if (!nominee.phoneno || nominee.phoneno.length < 9) errors.push('Please enter your Nominee (Next of Kin) Phone Number.')
    if (!econts.value[0].name || !econts.value[0].name.trim()) errors.push('Please enter Primary Emergency Contact Relative Name.')
    if (!econts.value[0].phoneno || econts.value[0].phoneno.length < 9) errors.push('Please enter Primary Emergency Contact Phone Number.')
  } else if (step === 3) {
    if (edus.value.length === 0 || !edus.value[0].schoolname || !edus.value[0].schoolname.trim()) {
      errors.push('Please enter at least one school / qualification under Educational Background.')
    }
    if (refs.value.length === 0 || !refs.value[0].name || !refs.value[0].name.trim()) {
      errors.push('Please provide at least one Character / Professional Referee.')
    }
  } else if (step === 4) {
    if (!banksocial.bankname) errors.push('Please select your Bank Name.')
    if (!banksocial.bankbranch || !banksocial.bankbranch.trim()) errors.push('Please enter your Bank Branch.')
    if (!banksocial.accountname || !banksocial.accountname.trim()) errors.push('Please enter your Bank Account Name.')
    if (!banksocial.accountnumber || !banksocial.accountnumber.trim()) errors.push('Please enter your Bank Account Number.')
    if (!guarantor.name || !guarantor.name.trim()) errors.push('Please provide your Guarantor Full Name.')
    if (!guarantor.relation || !guarantor.relation.trim()) errors.push('Please enter your Relationship to Guarantor.')
    if (!guarantor.phoneno || guarantor.phoneno.length < 9) errors.push('Please enter Guarantor Mobile Phone Number.')
  } else if (step === 5) {
    if (!hasSignature.value && !emp.signature) errors.push('Please provide your Touch Digital Signature.')
    if (!agreedDeclaration.value) errors.push('You must check the box agreeing to the Melcom Employee Declaration.')
  }

  validationErrors.value = errors
  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Information',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'Review Fields'
    })
    return false
  }
  return true
}

// Canvas Touch Signature Logic
const initCanvas = () => {
  const canvas = sigCanvas.value
  if (!canvas) return
  const ratio = Math.max(window.devicePixelRatio || 1, 1)
  const rect = canvas.getBoundingClientRect()
  canvas.width = rect.width * ratio
  canvas.height = rect.height * ratio
  canvasCtx = canvas.getContext('2d')
  canvasCtx.scale(ratio, ratio)
  canvasCtx.strokeStyle = '#0f172a'
  canvasCtx.lineWidth = 2.5
  canvasCtx.lineCap = 'round'
  canvasCtx.lineJoin = 'round'

  // If restoring existing signature image
  if (emp.signature) {
    const img = new Image()
    img.onload = () => {
      canvasCtx.drawImage(img, 0, 0, rect.width, rect.height)
      hasSignature.value = true
    }
    img.src = emp.signature
  }
}

const getCanvasPos = (evt) => {
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  return {
    x: evt.clientX - rect.left,
    y: evt.clientY - rect.top
  }
}

const startDrawing = (e) => {
  isDrawing.value = true
  const pos = getCanvasPos(e)
  canvasCtx.beginPath()
  canvasCtx.moveTo(pos.x, pos.y)
}

const draw = (e) => {
  if (!isDrawing.value) return
  const pos = getCanvasPos(e)
  canvasCtx.lineTo(pos.x, pos.y)
  canvasCtx.stroke()
  hasSignature.value = true
}

const stopDrawing = () => {
  if (!isDrawing.value) return
  isDrawing.value = false
  saveSignatureData()
}

// Touch events for mobile phones (iOS & Android)
const handleTouchStart = (e) => {
  if (e.touches && e.touches[0]) {
    isDrawing.value = true
    const touch = e.touches[0]
    const canvas = sigCanvas.value
    const rect = canvas.getBoundingClientRect()
    canvasCtx.beginPath()
    canvasCtx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top)
  }
}

const handleTouchMove = (e) => {
  if (!isDrawing.value || !e.touches || !e.touches[0]) return
  const touch = e.touches[0]
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  canvasCtx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top)
  canvasCtx.stroke()
  hasSignature.value = true
}

const handleTouchEnd = () => {
  isDrawing.value = false
  saveSignatureData()
}

const saveSignatureData = () => {
  if (!sigCanvas.value) return
  emp.signature = sigCanvas.value.toDataURL('image/png')
  saveDraftToStorage()
}

const clearSignature = () => {
  if (!sigCanvas.value || !canvasCtx) return
  const canvas = sigCanvas.value
  const rect = canvas.getBoundingClientRect()
  canvasCtx.clearRect(0, 0, rect.width, rect.height)
  hasSignature.value = false
  emp.signature = null
  saveDraftToStorage()
}

// LocalStorage Draft Management
const STORAGE_KEY = 'melcom_online_onboarding_draft'

const saveDraftToStorage = (showNotification = false) => {
  try {
    const draftPayload = {
      emp,
      spouse,
      childrens: childrens.value,
      nominee,
      econts: econts.value,
      edus: edus.value,
      workexps: workexps.value,
      refs: refs.value,
      banksocial,
      guarantor,
      documents,
      currentStep: currentStep.value,
      savedAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draftPayload))
    draftSavedAt.value = draftPayload.savedAt

    if (showNotification) {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Draft saved to this device',
        showConfirmButton: false,
        timer: 2000
      })
    }
  } catch (err) {
    console.warn('LocalStorage save error:', err)
  }
}

const loadDraftFromStorage = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return
    const d = JSON.parse(raw)
    if (d.emp) Object.assign(emp, d.emp)
    if (d.spouse) Object.assign(spouse, d.spouse)
    if (d.childrens) childrens.value = d.childrens
    if (d.nominee) Object.assign(nominee, d.nominee)
    if (d.econts) econts.value = d.econts
    if (d.edus) edus.value = d.edus
    if (d.workexps) workexps.value = d.workexps
    if (d.refs) refs.value = d.refs
    if (d.banksocial) Object.assign(banksocial, d.banksocial)
    if (d.guarantor) Object.assign(guarantor, d.guarantor)
    if (d.documents) Object.assign(documents, d.documents)
    if (d.currentStep && d.currentStep >= 1 && d.currentStep <= 5) currentStep.value = d.currentStep
    if (d.savedAt) draftSavedAt.value = d.savedAt
    if (emp.signature) hasSignature.value = true
  } catch (e) {
    console.warn('Failed to load draft:', e)
  }
}

const promptClearDraft = () => {
  Swal.fire({
    title: 'Reset Onboarding Form?',
    text: 'This will clear all entered information and start a blank application.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Reset Form'
  }).then((result) => {
    if (result.isConfirmed) {
      localStorage.removeItem(STORAGE_KEY)
      window.location.reload()
    }
  })
}

// Final Submission
const submitApplication = async () => {
  for (let s = 1; s <= 5; s++) {
    if (!validateStep(s)) {
      currentStep.value = s
      window.scrollTo({ top: 0, behavior: 'smooth' })
      return
    }
  }

  submitting.value = true

  const payload = {
    emp: {
      ...emp,
      signature: emp.signature || (sigCanvas.value ? sigCanvas.value.toDataURL('image/png') : null)
    },
    spouse,
    childrens: childrens.value,
    nominee,
    econts: econts.value,
    edus: edus.value,
    workexps: workexps.value,
    refs: refs.value,
    banksocial,
    guarantor,
    irrguar: {
      name: guarantor.name,
      occupation: guarantor.occupation,
      address: guarantor.address,
      phoneno: guarantor.phoneno,
      amount: '5000'
    },
    documents
  }

  try {
    const res = await axios.post('/public/onboard', payload)

    submitting.value = false
    submittedSuccess.value = true
    localStorage.removeItem(STORAGE_KEY)

    Object.assign(successData, {
      reference_number: res.data.reference_number || 'MEL-ONB-PROCESSED',
      employee_code: res.data.employee_code || 'ONB2026',
      candidate_name: res.data.candidate_name || `${emp.firstname} ${emp.surname}`,
      position: res.data.position || emp.joiningposition,
      submission_date: res.data.submission_date || new Date().toLocaleString()
    })

    window.scrollTo({ top: 0, behavior: 'smooth' })

    Swal.fire({
      icon: 'success',
      title: 'Application Submitted!',
      text: 'Your Melcom onboarding application has been successfully submitted.',
      confirmButtonColor: '#dc2626'
    })
  } catch (error) {
    submitting.value = false
    console.error('Submission error:', error)
    const msg = error.response?.data?.message || 'Failed to submit onboarding form. Please verify your connection and try again.'
    Swal.fire({
      icon: 'error',
      title: 'Submission Error',
      text: msg,
      confirmButtonColor: '#dc2626'
    })
  }
}

// Copy Reference Number
const copyReference = async (ref) => {
  if (!ref) return
  try {
    await navigator.clipboard.writeText(ref)
    copyStatusText.value = 'Copied!'
    setTimeout(() => {
      copyStatusText.value = 'Copy'
    }, 2500)
  } catch (e) {
    copyStatusText.value = 'Copied'
  }
}

const printSummary = () => {
  window.print()
}

const startNewApplication = () => {
  localStorage.removeItem(STORAGE_KEY)
  window.location.reload()
}

// Auto-save debounced watcher
let autoSaveTimer = null
watch(
  [emp, nominee, banksocial, guarantor, spouse, childrens, edus, workexps, refs, documents],
  () => {
    if (submittedSuccess.value) return
    clearTimeout(autoSaveTimer)
    autoSaveTimer = setTimeout(() => {
      saveDraftToStorage(false)
    }, 800)
  },
  { deep: true }
)

onMounted(() => {
  loadDraftFromStorage()
  window.addEventListener('resize', initCanvas)
  if (currentStep.value === 5) {
    nextTick(initCanvas)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', initCanvas)
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.25s ease-out forwards;
}

@media print {
  header, button, .sticky, input, select {
    box-shadow: none !important;
  }
}
</style>
