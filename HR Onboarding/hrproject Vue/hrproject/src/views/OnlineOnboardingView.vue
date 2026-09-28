<template>
  <div class="min-h-screen bg-slate-100 text-slate-800 font-sans selection:bg-blue-100 selection:text-blue-900 pb-28 sm:pb-16">
    
    <!-- ======================================================== -->
    <!-- TOP PMS STANDARD HEADER BANNER (Identical to Main Onb.)  -->
    <!-- ======================================================== -->
    <header class="bg-[#1A237E] text-white shadow-xl relative overflow-hidden">
      <!-- Decorative background accent circles -->
      <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-white/5 pointer-events-none"></div>
      <div class="absolute -left-10 -top-10 w-36 h-36 rounded-full bg-blue-500/10 pointer-events-none"></div>

      <div class="max-w-5xl mx-auto px-4 py-5 sm:px-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          
          <!-- Title & Subtitle with User Icon -->
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-blue-200 border border-white/15 shrink-0 shadow-inner">
              <i class="pi pi-user-plus text-2xl text-blue-200"></i>
            </div>
            <div>
              <p class="text-white/80 text-[10px] sm:text-xs font-black uppercase tracking-wider mb-0.5">
                HUMAN RESOURCES &amp; TALENT ACQUISITION
              </p>
              <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight leading-tight m-0">
                Employee Onboarding &amp; Registration
              </h1>
              <p class="text-blue-100/70 text-xs font-medium mt-0.5 hidden sm:block">
                Complete registration, position details, banking, guarantee records and compliance checklists
              </p>
            </div>
          </div>

          <!-- Online Candidate Badge & Draft Status -->
          <div class="flex items-center gap-2 self-end sm:self-center">
            <div v-if="draftSavedAt" class="px-2.5 py-1 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-[11px] font-semibold flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Draft saved {{ draftSavedAt }}</span>
            </div>
            <button
              type="button"
              @click="promptClearDraft"
              title="Reset and start clean form"
              class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold transition flex items-center gap-1 cursor-pointer"
            >
              <i class="pi pi-refresh text-xs"></i>
              <span class="hidden sm:inline">Reset</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-3 sm:px-6 pt-5 pb-12">
      
      <!-- ======================================================== -->
      <!-- SUCCESS SCREEN (Application Confirmation)                -->
      <!-- ======================================================== -->
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
          Your Melcom employee onboarding registration has been securely transmitted to the Melcom HR Department.
        </p>

        <!-- Reference Box -->
        <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl p-5 max-w-md mx-auto mb-8 text-left">
          <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200">
            <span class="text-xs uppercase font-bold text-slate-500">Tracking Reference</span>
            <button
              type="button"
              @click="copyReference(successData.reference_number)"
              class="text-xs text-[#1A237E] hover:underline font-bold flex items-center gap-1 cursor-pointer"
            >
              <i class="pi pi-copy text-xs"></i>
              <span>{{ copyStatusText }}</span>
            </button>
          </div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono tracking-wider break-all mb-4 text-center py-2 bg-white rounded-lg border border-slate-200 shadow-xs">
            {{ successData.reference_number || 'MEL-ONB-PROCESSED' }}
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Position:</span>
              <span class="font-bold text-slate-800">{{ emp.joiningposition || 'Staff' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Company / Branch:</span>
              <span class="font-bold text-slate-800">{{ getCompanyName(emp.joining_company_id) }} ({{ getBranchName(emp.joining_branch_id) }})</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <span class="text-slate-500 font-medium">Temporary Code:</span>
              <span class="font-mono font-bold text-[#1A237E]">{{ successData.employee_code || 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-slate-500 font-medium">Submitted At:</span>
              <span class="font-semibold text-slate-700">{{ successData.submission_date || new Date().toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <button
            type="button"
            @click="printSummary"
            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-[#1A237E] text-white font-bold text-sm hover:bg-[#121858] transition flex items-center justify-center gap-2 shadow-md cursor-pointer"
          >
            <i class="pi pi-print"></i>
            Print / Save Confirmation (PDF)
          </button>
          <button
            type="button"
            @click="startNewApplication"
            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition cursor-pointer"
          >
            Submit Another Application
          </button>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- MAIN ONBOARDING FORM UI (100% Word-for-Word Parity)     -->
      <!-- ======================================================== -->
      <form v-else @submit.prevent="handleFormAction" novalidate>
        
        <!-- Modern PMS Filter Pill Tabs (Identical to Main Onboarding) -->
        <div class="tabs mb-5 bg-slate-200/80 p-1.5 rounded-2xl border border-slate-300 flex overflow-x-auto no-scrollbar gap-1.5 shadow-xs select-none">
          <button
            type="button"
            @click="maintab = 1"
            :class="maintab == 1 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-user text-xs"></i>
            <span>Employee Information Form</span>
          </button>

          <button
            type="button"
            @click="maintab = 2"
            :class="maintab == 2 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-credit-card text-xs"></i>
            <span>Bank / Social Security Fund</span>
          </button>

          <button
            type="button"
            @click="maintab = 3"
            :class="maintab == 3 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-shield text-xs"></i>
            <span>Irrevocable Continuing Guarantee</span>
          </button>

          <button
            type="button"
            @click="maintab = 4"
            :class="maintab == 4 ? 'activetab' : ''"
            class="tab-btn shrink-0"
          >
            <i class="pi pi-check-square text-xs"></i>
            <span>Checklist &amp; Compliance</span>
          </button>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 1: EMPLOYEE INFORMATION FORM                         -->
        <!-- ======================================================== -->
        <div v-show="maintab == 1" class="space-y-6">
          
          <!-- Sub-header with Title & Page Stepper [ 1 ] / [ 4 ] -->
          <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight flex items-center gap-2 m-0">
                <span>Employee Information Form</span>
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5 mb-0">Capture employee personal, position, contact and educational details</p>
            </div>
            
            <!-- Page Stepper matching main onboarding -->
            <div class="flex items-center gap-2 self-start sm:self-center">
              <span class="text-xs font-black uppercase tracking-wider text-slate-500">PAGE</span>
              <div class="flex items-center gap-1">
                <button
                  v-for="p in 4"
                  :key="p"
                  type="button"
                  @click="perinfostep = p"
                  class="w-7 h-7 rounded-lg text-xs font-black transition flex items-center justify-center border cursor-pointer"
                  :class="perinfostep === p ? 'bg-[#1A237E] text-white border-[#1A237E] shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                >
                  {{ p }}
                </button>
              </div>
              <span class="text-xs font-bold text-slate-400">/ 4</span>
            </div>
          </div>

          <!-- ─── PAGE 1: Position Details & Personal Information ─── -->
          <div v-show="perinfostep == 1" class="space-y-6 animate-fade-in">
            
            <!-- Section: MELCOM EMPLOYEE POSITION DETAILS -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>MELCOM EMPLOYEE POSITION DETAILS</span>
              </div>
              
              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                  <div>
                    <label class="form-label">Joining Company *:</label>
                    <select v-model="emp.joining_company_id" class="form-select" required>
                      <option v-for="comp in companyList" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Contract Category *:</label>
                    <select v-model="emp.contracttype" class="form-select" required>
                      <option v-for="ct in contractTypes" :key="ct.code" :value="ct.code">{{ ct.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Location *:</label>
                    <select v-model="emp.joining_branch_id" class="form-select" required>
                      <option value="" disabled>-- Select Location --</option>
                      <option v-for="b in branchList" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Department *:</label>
                    <select v-model="emp.joining_dept_id" class="form-select" required>
                      <option value="" disabled>-- Select Department --</option>
                      <option v-for="d in deptList" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label">Joining Position *:</label>
                    <input
                      type="text"
                      v-model="emp.joiningposition"
                      placeholder="e.g. CASHIER, SALES EXECUTIVE"
                      @input="emp.joiningposition = (emp.joiningposition || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Date of Joining *:</label>
                    <input
                      type="date"
                      v-model="emp.joiningdate"
                      class="form-input"
                      required
                      min="1986-01-01"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: PERSONAL INFORMATION -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>PERSONAL INFORMATION</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-5">
                
                <!-- Profile Picture Upload Zone -->
                <div>
                  <label class="form-label mb-2">Profile Picture *:</label>
                  <div class="profile-upload-zone cursor-pointer" @click="triggerProfileUpload">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-center sm:text-left">
                      <div class="w-16 h-16 rounded-full bg-slate-100 border-2 border-slate-200 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                        <img v-if="emp.profilepicture" :src="emp.profilepicture" alt="Profile Preview" class="w-full h-full object-cover" />
                        <i v-else class="pi pi-user text-2xl text-slate-400"></i>
                      </div>
                      <div>
                        <p class="font-bold text-slate-800 text-sm mb-0.5">Click to upload photo or take selfie</p>
                        <p class="text-xs text-slate-400 mb-2">PNG, JPG or JPEG up to 5MB</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#1A237E] text-white text-xs font-bold shadow-xs">
                          <i class="pi pi-camera"></i>
                          <span>{{ emp.profilepicture ? 'Change Photo' : 'Select Image' }}</span>
                        </span>
                        <input
                          ref="profileInputRef"
                          type="file"
                          accept="image/*"
                          capture="user"
                          @change="handleProfilePhoto"
                          class="hidden"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Personal Identity Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="form-label">First Name *:</label>
                    <input
                      type="text"
                      v-model="emp.firstname"
                      placeholder="e.g. KWAME"
                      @input="onNameChange"
                      class="form-input uppercase"
                      required
                    />
                  </div>

                  <div>
                    <label class="form-label">Middle Name:</label>
                    <input
                      type="text"
                      v-model="emp.middlename"
                      placeholder="e.g. KOFI"
                      @input="emp.middlename = (emp.middlename || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>

                  <div>
                    <label class="form-label">SurName *:</label>
                    <input
                      type="text"
                      v-model="emp.surname"
                      placeholder="e.g. MENSAH"
                      @input="onNameChange"
                      class="form-input uppercase"
                      required
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Citizenship *: </label>
                    <select v-model="emp.citizenship" class="form-select" required>
                      <option v-for="c in countryList" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </select>
                  </div>

                  <div v-if="emp.citizenship != 'GH'">
                    <label class="form-label">ID Type *:</label>
                    <select v-model="emp.idtype" class="form-select" required>
                      <option v-for="t in idTypeList" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                  </div>
                </div>

                <!-- Ghana Card / National ID Section -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" v-if="emp.citizenship == 'GH'">
                  <div>
                    <label class="form-label">Ghana Card No *:</label>
                    <input
                      type="text"
                      v-model="emp.ghcardno"
                      placeholder="GHA-000000000-0"
                      @input="formatGhCard"
                      class="form-input uppercase font-mono"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Ghana Card Picture *:</label>
                    <div class="flex items-center gap-2">
                      <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xs">
                        <i class="pi pi-camera"></i>
                        <span>{{ emp.ghcardfile ? 'Change Ghana Card' : 'Upload Ghana Card' }}</span>
                        <input type="file" accept="image/*" @change="e => handleFileField(e, 'ghcardfile')" class="hidden" />
                      </label>
                      <span v-if="emp.ghcardfile" class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                        <i class="pi pi-check"></i> Attached
                      </span>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" v-else>
                  <div>
                    <label class="form-label">Passport / ID No *:</label>
                    <input
                      type="text"
                      v-model="emp.ghcardno"
                      placeholder="Passport / ID Number"
                      @input="emp.ghcardno = (emp.ghcardno || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Passport / ID Picture *:</label>
                    <div class="flex items-center gap-2">
                      <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xs">
                        <i class="pi pi-camera"></i>
                        <span>{{ emp.ghcardfile ? 'Change ID' : 'Upload ID' }}</span>
                        <input type="file" accept="image/*" @change="e => handleFileField(e, 'ghcardfile')" class="hidden" />
                      </label>
                      <span v-if="emp.ghcardfile" class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                        <i class="pi pi-check"></i> Attached
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Addresses -->
                <div>
                  <label class="form-label">Residential Address/Landmark *:</label>
                  <input
                    type="text"
                    v-model="emp.raddress"
                    placeholder="e.g. Hse No. 12, Spintex Road, near Shell, Accra"
                    @input="emp.raddress = (emp.raddress || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Residential Ghana Digital Address *:</label>
                    <input
                      type="text"
                      v-model="emp.daddress"
                      placeholder="e.g. GA-183-9024"
                      @input="emp.daddress = (emp.daddress || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Permanent Home Town/Region *:</label>
                    <input
                      type="text"
                      v-model="emp.hometown"
                      placeholder="e.g. KUMASI / ASHANTI REGION"
                      @input="emp.hometown = (emp.hometown || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Permanent Ghana Digital Address *:</label>
                    <input
                      type="text"
                      v-model="emp.hdaddress"
                      placeholder="e.g. AK-022-1234"
                      @input="emp.hdaddress = (emp.hdaddress || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Mobile Number *:</label>
                    <input
                      type="tel"
                      v-model="emp.mobileno"
                      placeholder="e.g. 0244123456"
                      class="form-input"
                      required
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="form-label">Alternate Phone:</label>
                    <input
                      type="tel"
                      v-model="emp.altnumber"
                      placeholder="e.g. 0550123456"
                      class="form-input"
                    />
                  </div>
                  <div>
                    <label class="form-label">Email Address *:</label>
                    <input
                      type="email"
                      v-model="emp.email"
                      placeholder="e.g. kwame@example.com"
                      class="form-input"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Gender *:</label>
                    <select v-model="emp.gender" class="form-select" required>
                      <option value="M">MALE</option>
                      <option value="F">FEMALE</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="form-label">Social Security Number:</label>
                    <input
                      type="text"
                      v-model="emp.socialsecurityno"
                      placeholder="e.g. C012345678912"
                      @input="emp.socialsecurityno = (emp.socialsecurityno || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>
                  <div>
                    <div class="flex items-center justify-between mb-1">
                      <label class="form-label mb-0">Date of Birth *:</label>
                      <span v-if="computedAge !== null" class="text-[11px] font-black text-[#1A237E] px-2 py-0.5 bg-blue-50 border border-blue-200 rounded">
                        Age: {{ computedAge }} yrs
                      </span>
                    </div>
                    <input
                      type="date"
                      v-model="emp.dob"
                      :max="maxDobDate"
                      class="form-input"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Marital Status *:</label>
                    <select v-model="emp.maritalstatus" class="form-select" required>
                      <option v-for="m in maritalList" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                  </div>
                </div>

                <!-- Spouses information (v-if Marital Status == Married) -->
                <div v-if="emp.maritalstatus == 2" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black uppercase text-[#1A237E] m-0">Spouses information</h4>
                    <button
                      type="button"
                      @click="addSpouse"
                      class="px-2.5 py-1 rounded-lg bg-[#1A237E] text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                    >
                      <i class="pi pi-plus text-[10px]"></i>
                      <span>Add Spouse</span>
                    </button>
                  </div>

                  <div v-if="wives.length === 0" class="text-xs text-slate-500 italic">
                    Tap "Add Spouse" to record spouse full name and occupation.
                  </div>

                  <div v-for="(wife, index) in wives" :key="index" class="p-3 bg-white rounded-lg border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                    <div>
                      <label class="form-label">Spouse {{ index + 1 }}'s Name *:</label>
                      <input
                        type="text"
                        v-model="wife.name"
                        placeholder="e.g. GRACE MENSAH"
                        @input="wife.name = (wife.name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="flex-1">
                        <label class="form-label">Occupation *:</label>
                        <input
                          type="text"
                          v-model="wife.occupation"
                          placeholder="e.g. TEACHER, NURSE"
                          @input="wife.occupation = (wife.occupation || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <button
                        type="button"
                        @click="removeSpouse(index)"
                        class="h-10 w-10 rounded-xl text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition flex items-center justify-center shrink-0 cursor-pointer"
                      >
                        <i class="pi pi-trash text-xs"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Parents -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Father's Name *:</label>
                    <input
                      type="text"
                      v-model="emp.fathersname"
                      placeholder="e.g. JOHN MENSAH SR."
                      @input="emp.fathersname = (emp.fathersname || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Mother's Name *:</label>
                    <input
                      type="text"
                      v-model="emp.mothersname"
                      placeholder="e.g. MARY MENSAH"
                      @input="emp.mothersname = (emp.mothersname || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>
                </div>

                <!-- Do you know anyone in Melcom? -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-black text-slate-800">Do you know anyone in Melcom? *</span>
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="emp.relativeinorg" :value="0" class="text-[#1A237E]" />
                        <span>No</span>
                      </label>
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="emp.relativeinorg" :value="1" class="text-[#1A237E]" />
                        <span>Yes</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="emp.relativeinorg == 1" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200">
                    <div>
                      <label class="form-label">Name *:</label>
                      <input
                        type="text"
                        v-model="emp.relative_name"
                        placeholder="e.g. PETER ADDO"
                        @input="emp.relative_name = (emp.relative_name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Type of relation *:</label>
                      <select v-model="emp.relative_relation" class="form-select" required>
                        <option value="" disabled>-- Select Relation --</option>
                        <option v-for="r in relationList" :key="r.id" :value="r.id">{{ r.name }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Do you have children? -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-black text-slate-800">Do you have children? *</span>
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="haschildren" :value="false" class="text-[#1A237E]" />
                        <span>No</span>
                      </label>
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="haschildren" :value="true" class="text-[#1A237E]" />
                        <span>Yes</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="haschildren" class="space-y-3 pt-2 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-bold text-slate-600">List dependent children:</span>
                      <button
                        type="button"
                        @click="addChild"
                        class="px-2.5 py-1 rounded-lg bg-[#1A237E] text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                      >
                        <i class="pi pi-plus text-[10px]"></i>
                        <span>Add child</span>
                      </button>
                    </div>

                    <div v-for="(child, index) in childrens" :key="index" class="p-3 bg-white rounded-lg border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                      <div>
                        <label class="form-label">Child {{ index + 1 }}'s Name *:</label>
                        <input
                          type="text"
                          v-model="child.name"
                          placeholder="e.g. KOFI MENSAH"
                          @input="child.name = (child.name || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <div class="flex items-center gap-2">
                        <div class="flex-1">
                          <label class="form-label">Age *:</label>
                          <input
                            type="number"
                            v-model.number="child.age"
                            placeholder="Age in years"
                            class="form-input"
                            min="0"
                            max="50"
                            required
                          />
                        </div>
                        <button
                          type="button"
                          @click="removeChild(index)"
                          class="h-10 w-10 rounded-xl text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition flex items-center justify-center shrink-0 cursor-pointer"
                        >
                          <i class="pi pi-trash text-xs"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Work Permit Details (v-if contracttype == 'expat') -->
                <div v-if="emp.contracttype == 'expat'" class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 space-y-3">
                  <h4 class="text-xs font-black uppercase text-amber-900 m-0">Work Permit Details</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Number Of Renewal *:</label>
                      <input type="number" v-model.number="wpermit.renewalno" class="form-input" required />
                    </div>
                    <div>
                      <label class="form-label">Passport Expiry Date *:</label>
                      <input type="date" v-model="wpermit.passedate" class="form-input" required />
                    </div>
                    <div>
                      <label class="form-label">Work Permit Date *:</label>
                      <input type="date" v-model="wpermit.idate" class="form-input" required />
                    </div>
                    <div>
                      <label class="form-label">Work Permit Expiry Date *:</label>
                      <input type="date" v-model="wpermit.edate" class="form-input" required />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="form-label">Work Permit Number *:</label>
                      <input
                        type="text"
                        v-model="wpermit.number"
                        placeholder="e.g. WP-123456-GH"
                        @input="wpermit.number = (wpermit.number || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                  </div>
                </div>

                <!-- Driver's Licence Details (v-if joiningposition == 'DRIVER') -->
                <div v-if="emp.joiningposition == 'DRIVER'" class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 space-y-3">
                  <h4 class="text-xs font-black uppercase text-[#1A237E] m-0">Driver's Licence Details</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Name on the license *:</label>
                      <input
                        type="text"
                        v-model="dlicense.name"
                        placeholder="e.g. KWAME MENSAH"
                        @input="dlicense.name = (dlicense.name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">license# *:</label>
                      <input
                        type="text"
                        v-model="dlicense.licenseno"
                        placeholder="e.g. B-12345678"
                        @input="dlicense.licenseno = (dlicense.licenseno || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Issuing Date *:</label>
                      <input type="date" v-model="dlicense.idate" class="form-input" required />
                    </div>
                    <div>
                      <label class="form-label">Expiry Date *:</label>
                      <input type="date" v-model="dlicense.edate" class="form-input" required />
                    </div>
                    <div>
                      <label class="form-label">Ref# *:</label>
                      <input
                        type="text"
                        v-model="dlicense.ref"
                        placeholder="e.g. RF-987"
                        @input="dlicense.ref = (dlicense.ref || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Renewal Date *:</label>
                      <input type="date" v-model="dlicense.rdate" class="form-input" required />
                    </div>
                  </div>
                </div>

                <!-- Job at Melcom (Have you ever worked with Melcom?) -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-black text-slate-800">Have you ever worked with Melcom? *</span>
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="iscurwork" :value="false" class="text-[#1A237E]" />
                        <span>No</span>
                      </label>
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="iscurwork" :value="true" class="text-[#1A237E]" />
                        <span>Yes</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="iscurwork" class="pt-2 border-t border-slate-200 space-y-3">
                    <h5 class="text-xs font-bold text-[#1A237E] uppercase m-0">Details of Job at Melcom</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div>
                        <label class="form-label">Title *:</label>
                        <input
                          type="text"
                          v-model="curwork.title"
                          placeholder="e.g. CASHIER"
                          @input="curwork.title = (curwork.title || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <div>
                        <label class="form-label">Employee ID *:</label>
                        <input
                          type="text"
                          v-model="curwork.employeeid"
                          placeholder="e.g. MEL12345"
                          @input="curwork.employeeid = (curwork.employeeid || '').toUpperCase()"
                          class="form-input uppercase font-mono"
                          required
                        />
                      </div>
                      <div>
                        <label class="form-label">Date of Joining *:</label>
                        <input type="date" v-model="curwork.doj" class="form-input" required min="1986-01-01" />
                      </div>
                      <div>
                        <label class="form-label">Department *:</label>
                        <select v-model="curwork.dept_id" class="form-select" required>
                          <option value="" disabled>-- Select Department --</option>
                          <option v-for="dept in deptList" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                        </select>
                      </div>
                      <div>
                        <label class="form-label">Branch *:</label>
                        <select v-model="curwork.branch_id" class="form-select" required>
                          <option value="" disabled>-- Select Branch --</option>
                          <option v-for="branch in branchList" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                      </div>
                      <div>
                        <label class="form-label">Region *:</label>
                        <select v-model="curwork.region_id" class="form-select" required>
                          <option value="" disabled>-- Select Region --</option>
                          <option v-for="reg in regionList" :key="reg.id" :value="reg.id">{{ reg.name }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Union membership information -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="text-xs font-black text-slate-800">Are you Union Member? *</span>
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="isunion" :value="false" class="text-[#1A237E]" />
                        <span>No</span>
                      </label>
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="isunion" :value="true" class="text-[#1A237E]" />
                        <span>Yes</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="isunion" class="pt-2 border-t border-slate-200 space-y-3">
                    <h5 class="text-xs font-bold text-[#1A237E] uppercase m-0">Details of Membership</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div>
                        <label class="form-label">Union name *:</label>
                        <input
                          type="text"
                          v-model="unioninfo.union_name"
                          placeholder="e.g. ICU GHANA"
                          @input="unioninfo.union_name = (unioninfo.union_name || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <div>
                        <label class="form-label">Union Registration date *:</label>
                        <input type="date" v-model="unioninfo.union_regdate" class="form-input" required />
                      </div>
                      <div>
                        <label class="form-label">Union Registration Number *:</label>
                        <input
                          type="text"
                          v-model="unioninfo.union_number"
                          placeholder="e.g. UN-98765"
                          @input="unioninfo.union_number = (unioninfo.union_number || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ANY OTHER INFORMATION -->
                <div>
                  <label class="form-label">ANY OTHER INFORMATION:</label>
                  <textarea
                    v-model="emp.anyotherinfo"
                    rows="3"
                    placeholder="Enter any additional details or background notes..."
                    @input="emp.anyotherinfo = (emp.anyotherinfo || '').toUpperCase()"
                    class="form-textarea uppercase"
                  ></textarea>
                </div>

              </div>
            </div>
          </div>

          <!-- ─── PAGE 2: Emergency Contact & Social Contact ─── -->
          <div v-show="perinfostep == 2" class="space-y-6 animate-fade-in">
            
            <!-- Section: Emergency Contact Information [Relatives Only] -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>Emergency Contact Information [Relatives Only]</span>
                </div>
                <button
                  type="button"
                  @click="addEcont"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div v-for="(econt, index) in econts" :key="index" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <span class="text-xs font-black uppercase text-[#1A237E]">CONTACT NO {{ index + 1 }}</span>
                    <button
                      v-if="econts.length > 1"
                      type="button"
                      @click="removeEcont(index)"
                      class="text-xs text-red-600 font-bold hover:underline cursor-pointer"
                    >
                      Remove
                    </button>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Full Name *:</label>
                      <input
                        type="text"
                        v-model="econt.fullname"
                        placeholder="e.g. KWAME MENSAH"
                        @input="econt.fullname = (econt.fullname || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Relationship to you *:</label>
                      <select v-model="econt.relation" class="form-select" required>
                        <option value="" disabled>-- Select Relationship --</option>
                        <option v-for="rel in familyRelList" :key="rel.id" :value="rel.id">{{ rel.name }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Work Address preferable:</label>
                      <input
                        type="text"
                        v-model="econt.workaddress"
                        placeholder="e.g. KORLE BU HOSPITAL"
                        @input="econt.workaddress = (econt.workaddress || '').toUpperCase()"
                        class="form-input uppercase"
                      />
                    </div>
                    <div>
                      <label class="form-label">Ghana Card No :</label>
                      <input
                        type="text"
                        v-model="econt.ghcardno"
                        placeholder="GHA-000000000-0"
                        @input="econt.ghcardno = (econt.ghcardno || '').toUpperCase()"
                        class="form-input uppercase font-mono"
                      />
                    </div>
                    <div>
                      <label class="form-label">Mobile Number *:</label>
                      <input
                        type="tel"
                        v-model="econt.mobileno"
                        placeholder="e.g. 0244112233"
                        class="form-input"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Alternate Phone:</label>
                      <input
                        type="tel"
                        v-model="econt.altnumber"
                        placeholder="e.g. 0550112233"
                        class="form-input"
                      />
                    </div>
                  </div>

                  <div class="pt-2 flex items-center justify-between border-t border-slate-200">
                    <span class="text-xs font-bold text-slate-700">Next of kin? *</span>
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="econt.isnextofkin" :value="0" class="text-[#1A237E]" />
                        <span>No</span>
                      </label>
                      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer">
                        <input type="radio" v-model="econt.isnextofkin" :value="1" class="text-[#1A237E]" />
                        <span>Yes</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: Social Contact Information -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>Social Contact Information</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Church / Mosque Membership &amp; Location:</label>
                    <input
                      type="text"
                      v-model="soccont.churchmemberloc"
                      placeholder="e.g. METHODIST CHURCH, SPINTEX"
                      @input="soccont.churchmemberloc = (soccont.churchmemberloc || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                  <div>
                    <label class="form-label">Pastor Religious Leader:</label>
                    <input
                      type="text"
                      v-model="soccont.pastor"
                      placeholder="e.g. REV. DANIEL MENSAH"
                      @input="soccont.pastor = (soccont.pastor || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                  <div>
                    <label class="form-label">Duration of Membership (Years):</label>
                    <input
                      type="number"
                      v-model.number="soccont.memduration"
                      placeholder="e.g. 5"
                      class="form-input"
                      min="0"
                    />
                  </div>
                  <div>
                    <label class="form-label">Mobile Number:</label>
                    <input
                      type="tel"
                      v-model="soccont.mobileno"
                      placeholder="e.g. 0244778899"
                      class="form-input"
                    />
                  </div>
                  <div>
                    <label class="form-label">Ghana Card No:</label>
                    <input
                      type="text"
                      v-model="soccont.ghcardno"
                      placeholder="GHA-000000000-0"
                      @input="soccont.ghcardno = (soccont.ghcardno || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>
                  <div>
                    <label class="form-label">Closest Friend/Confidant:</label>
                    <input
                      type="text"
                      v-model="soccont.closestfriend"
                      placeholder="e.g. KOFI ANANE"
                      @input="soccont.closestfriend = (soccont.closestfriend || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                  <div>
                    <label class="form-label">Contacts Phone:</label>
                    <input
                      type="tel"
                      v-model="soccont.contactsphone"
                      placeholder="e.g. 0244332211"
                      class="form-input"
                    />
                  </div>
                  <div>
                    <label class="form-label">Email Address:</label>
                    <input
                      type="email"
                      v-model="soccont.email"
                      placeholder="e.g. friend@example.com"
                      class="form-input"
                    />
                  </div>
                  <div>
                    <label class="form-label">Digital Address:</label>
                    <input
                      type="text"
                      v-model="soccont.digitaladdress"
                      placeholder="e.g. GA-123-4567"
                      @input="soccont.digitaladdress = (soccont.digitaladdress || '').toUpperCase()"
                      class="form-input uppercase font-mono"
                    />
                  </div>
                  <div>
                    <label class="form-label">Work Address preferable:</label>
                    <input
                      type="text"
                      v-model="soccont.workaddress"
                      placeholder="e.g. ACCRA HIGH STREET"
                      @input="soccont.workaddress = (soccont.workaddress || '').toUpperCase()"
                      class="form-input uppercase"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ─── PAGE 3: Education, Work Experience, References, Nominee ─── -->
          <div v-show="perinfostep == 3" class="space-y-6 animate-fade-in">
            
            <!-- Section: Educational Background -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>Educational Background</span>
                </div>
                <button
                  type="button"
                  @click="addEdu"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div v-for="(edu, index) in edus" :key="index" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Education Entry #{{ index + 1 }}</span>
                    <button
                      v-if="edus.length > 1"
                      type="button"
                      @click="removeEdu(index)"
                      class="text-xs text-red-600 font-bold hover:underline cursor-pointer"
                    >
                      Remove
                    </button>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Qualification Type *:</label>
                      <select v-model="edu.educqualtype" class="form-select" required>
                        <option v-for="q in qualList" :key="q.id" :value="q.id">{{ q.name }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Specialization / School *:</label>
                      <input
                        type="text"
                        v-model="edu.educqual"
                        placeholder="e.g. GENERAL ARTS - ACCRA ACADEMY"
                        @input="edu.educqual = (edu.educqual || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Date Of Completion:</label>
                      <input type="date" v-model="edu.to" class="form-input" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: WORK EXPERIENCE -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>WORK EXPERIENCE</span>
                </div>
                <button
                  type="button"
                  @click="addWorkExp"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div v-if="workexps.length === 0" class="p-4 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-500">
                  No prior work experience added. Tap "Add" if you have past employers.
                </div>
                <div v-else class="space-y-4">
                  <div v-for="(wexp, ind) in workexps" :key="ind" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                      <span class="text-xs font-black uppercase text-[#1A237E]">Experience #{{ ind + 1 }}</span>
                      <button type="button" @click="removeWorkExp(ind)" class="text-xs text-red-600 font-bold hover:underline cursor-pointer">
                        Remove
                      </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label class="form-label">Name of Organization *:</label>
                        <input
                          type="text"
                          v-model="wexp.orgname"
                          placeholder="e.g. ABC RETAIL LTD"
                          @input="wexp.orgname = (wexp.orgname || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <div>
                        <label class="form-label">Position held *:</label>
                        <input
                          type="text"
                          v-model="wexp.postheld"
                          placeholder="e.g. SALES ASSISTANT"
                          @input="wexp.postheld = (wexp.postheld || '').toUpperCase()"
                          class="form-input uppercase"
                          required
                        />
                      </div>
                      <div>
                        <label class="form-label">From *:</label>
                        <input type="date" v-model="wexp.from" class="form-input" required />
                      </div>
                      <div>
                        <label class="form-label">To:</label>
                        <input type="date" v-model="wexp.to" class="form-input" />
                      </div>
                      <div>
                        <label class="form-label">Reason for Leaving:</label>
                        <input
                          type="text"
                          v-model="wexp.reasonforleaving"
                          placeholder="e.g. Career advancement"
                          class="form-input"
                        />
                      </div>
                      <div>
                        <label class="form-label">Salary (Optional):</label>
                        <input
                          type="text"
                          v-model="wexp.salary"
                          placeholder="e.g. GHS 1,500"
                          class="form-input"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: REFERENCES -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>REFERENCES</span>
                </div>
                <button
                  type="button"
                  @click="addRef"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div v-for="(refItem, ind) in refs" :key="ind" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <span class="text-xs font-black uppercase text-[#1A237E]">REFERENCE {{ ind + 1 }}</span>
                    <button v-if="refs.length > 1" type="button" @click="removeRef(ind)" class="text-xs text-red-600 font-bold hover:underline cursor-pointer">
                      Remove
                    </button>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="form-label">Name *:</label>
                      <input
                        type="text"
                        v-model="refItem.name"
                        placeholder="e.g. REV. DANIEL APPIAH"
                        @input="refItem.name = (refItem.name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Company Name *:</label>
                      <input
                        type="text"
                        v-model="refItem.companyname"
                        placeholder="e.g. METHODIST CHURCH / GHANA EDUCATION SERVICE"
                        @input="refItem.companyname = (refItem.companyname || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Designation *:</label>
                      <input
                        type="text"
                        v-model="refItem.designation"
                        placeholder="e.g. HEADMASTER / SENIOR PASTOR"
                        @input="refItem.designation = (refItem.designation || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Contact Number *:</label>
                      <input
                        type="tel"
                        v-model="refItem.contactno"
                        placeholder="e.g. 0244778899"
                        class="form-input"
                        required
                      />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="form-label">Email:</label>
                      <input
                        type="email"
                        v-model="refItem.email"
                        placeholder="e.g. ref@example.com"
                        class="form-input"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section: Nominee Information (Next of Kin) -->
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>Nominee Information (Next of Kin)</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="form-label">Name *:</label>
                    <input
                      type="text"
                      v-model="nominee.name"
                      placeholder="e.g. MARY MENSAH"
                      @input="nominee.name = (nominee.name || '').toUpperCase()"
                      class="form-input uppercase"
                      required
                    />
                  </div>
                  <div>
                    <label class="form-label">Relationship to you *:</label>
                    <select v-model="nominee.relation" class="form-select" required>
                      <option value="" disabled>-- Select Relationship --</option>
                      <option v-for="rel in familyRelList" :key="rel.id" :value="rel.id">{{ rel.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Mobile Number *:</label>
                    <input
                      type="tel"
                      v-model="nominee.mobile"
                      placeholder="e.g. 0244112233"
                      class="form-input"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- ─── PAGE 4: Employee's Declaration & Touch Pad ─── -->
          <div v-show="perinfostep == 4" class="space-y-6 animate-fade-in">
            <div class="section-card">
              <div class="section-header">
                <span class="accent-bar"></span>
                <span>EMPLOYEE'S DECLARATION</span>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-6">
                <!-- Legal declaration text matching main onboarding word-for-word -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed font-medium">
                  <p class="m-0">
                    I <strong>{{ (emp.firstname + ' ' + emp.surname).trim() || 'Candidate' }}</strong> declare that the information provided above is true. In case of false declaration, the appropriate action as per company's policy will be taken.
                  </p>
                </div>

                <!-- Signature 1: Employee Signature Pad -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Signature:</span>
                    <button
                      type="button"
                      @click="clearEmployeeSignature"
                      class="px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                    >
                      <i class="pi pi-refresh text-[10px]"></i>
                      <span>Clear</span>
                    </button>
                  </div>

                  <p class="text-[11px] text-slate-500 m-0">Sign with finger or stylus on smartphone, or mouse on desktop.</p>

                  <div class="relative bg-white rounded-xl border-2 border-slate-300 overflow-hidden touch-none shadow-inner">
                    <canvas
                      ref="sig1Canvas"
                      class="w-full h-40 bg-white cursor-crosshair block"
                      @mousedown="startDrawSig1"
                      @mousemove="drawSig1"
                      @mouseup="stopDrawSig1"
                      @mouseleave="stopDrawSig1"
                      @touchstart.prevent="touchStartSig1"
                      @touchmove.prevent="touchMoveSig1"
                      @touchend.prevent="touchEndSig1"
                    ></canvas>
                    <div class="absolute bottom-2 left-4 right-4 border-b border-dashed border-slate-300 pointer-events-none flex justify-between text-[10px] text-slate-400 font-bold pb-0.5">
                      <span>Employee Signature Line</span>
                      <span>Digital Pad</span>
                    </div>
                  </div>

                  <div class="text-xs text-slate-600 font-semibold pt-1">
                    Date: <strong>{{ todayString }}</strong>
                  </div>
                </div>

                <!-- Signature 2: Guarantor Signature Pad -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Guarantor Signature:</span>
                    <button
                      type="button"
                      @click="clearGuarSignature"
                      class="px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                    >
                      <i class="pi pi-refresh text-[10px]"></i>
                      <span>Clear</span>
                    </button>
                  </div>

                  <p class="text-[11px] text-slate-500 m-0">Guarantor signs here or you may append the signed guarantor form.</p>

                  <div class="relative bg-white rounded-xl border-2 border-slate-300 overflow-hidden touch-none shadow-inner">
                    <canvas
                      ref="sigGuarCanvas"
                      class="w-full h-40 bg-white cursor-crosshair block"
                      @mousedown="startDrawSigGuar"
                      @mousemove="drawSigGuar"
                      @mouseup="stopDrawSigGuar"
                      @mouseleave="stopDrawSigGuar"
                      @touchstart.prevent="touchStartSigGuar"
                      @touchmove.prevent="touchMoveSigGuar"
                      @touchend.prevent="touchEndSigGuar"
                    ></canvas>
                    <div class="absolute bottom-2 left-4 right-4 border-b border-dashed border-slate-300 pointer-events-none flex justify-between text-[10px] text-slate-400 font-bold pb-0.5">
                      <span>Guarantor Signature Line</span>
                      <span>Digital Pad</span>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: BANK / SOCIAL SECURITY FUND                       -->
        <!-- ======================================================== -->
        <div v-show="maintab == 2" class="space-y-6 animate-fade-in">
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>Bank &amp; Social Security Fund Contribution Details</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-5">
              <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 text-[#1A237E] text-xs font-medium flex items-start gap-2">
                <i class="pi pi-info-circle text-blue-700 text-sm mt-0.5"></i>
                <span>Your monthly salary and allowances will be processed directly into this bank account in your name.</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div>
                  <label class="form-label">Name of Bank *:</label>
                  <select v-model="banksocial.bankname" class="form-select" required>
                    <option value="" disabled>-- Select Your Bank --</option>
                    <option v-for="b in bankList" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>

                <div>
                  <label class="form-label">Bank Branch *:</label>
                  <input
                    type="text"
                    v-model="banksocial.bankbranch"
                    placeholder="e.g. SPINTEX ROAD BRANCH"
                    @input="banksocial.bankbranch = (banksocial.bankbranch || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Account Name *:</label>
                  <input
                    type="text"
                    v-model="banksocial.accountname"
                    placeholder="e.g. KWAME MENSAH"
                    @input="banksocial.accountname = (banksocial.accountname || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Account Type *:</label>
                  <select v-model="banksocial.accounttype" class="form-select" required>
                    <option v-for="bat in bankAccTypes" :key="bat.id" :value="bat.id">{{ bat.name }}</option>
                  </select>
                </div>

                <div>
                  <label class="form-label">Account Number *:</label>
                  <input
                    type="text"
                    v-model="banksocial.accountnumber"
                    placeholder="e.g. 1011234567890"
                    class="form-input font-mono font-bold"
                    required
                  />
                </div>

                <div>
                  <label class="form-label">Social Security Fund Number (SSNIT):</label>
                  <input
                    type="text"
                    v-model="banksocial.socialfundnumber"
                    placeholder="e.g. C012345678912"
                    @input="banksocial.socialfundnumber = (banksocial.socialfundnumber || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="form-label">Tier 2 Pension Number (Petra Trust):</label>
                  <input
                    type="text"
                    v-model="banksocial.petratrustnumber"
                    placeholder="e.g. PT-123456"
                    @input="banksocial.petratrustnumber = (banksocial.petratrustnumber || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 3: IRREVOCABLE CONTINUING GUARANTEE (3 Pages)        -->
        <!-- ======================================================== -->
        <div v-show="maintab == 3" class="space-y-6 animate-fade-in">
          
          <!-- Sub-header with Stepper Page 1 / 3 -->
          <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight m-0">
                Irrevocable Continuing Guarantee &amp; Witness Sign-Off
              </h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5 mb-0">Guarantor information, financial indemnity declaration, and witnesses</p>
            </div>
            
            <div class="flex items-center gap-2 self-start sm:self-center">
              <span class="text-xs font-black uppercase tracking-wider text-slate-500">PAGE</span>
              <div class="flex items-center gap-1">
                <button
                  v-for="p in 3"
                  :key="p"
                  type="button"
                  @click="irrguarstep = p"
                  class="w-7 h-7 rounded-lg text-xs font-black transition flex items-center justify-center border cursor-pointer"
                  :class="irrguarstep === p ? 'bg-[#1A237E] text-white border-[#1A237E] shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                >
                  {{ p }}
                </button>
              </div>
              <span class="text-xs font-bold text-slate-400">/ 3</span>
            </div>
          </div>

          <!-- ─── PAGE 1: Information Form ─── -->
          <div v-show="irrguarstep == 1" class="section-card animate-fade-in">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>Information Form</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Name of Guarantor *:</label>
                  <input
                    type="text"
                    v-model="irrguar.guarname"
                    placeholder="e.g. DR. EMMANUEL ADDO"
                    @input="irrguar.guarname = (irrguar.guarname || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">SSF No *:</label>
                  <input
                    type="text"
                    v-model="irrguar.ssfno"
                    placeholder="e.g. C098765432100"
                    @input="irrguar.ssfno = (irrguar.ssfno || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Annual Income *:</label>
                  <input
                    type="number"
                    v-model.number="irrguar.annualincome"
                    placeholder="e.g. 60000"
                    class="form-input font-mono"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Value of Landed Property *:</label>
                  <input
                    type="number"
                    v-model.number="irrguar.propertyvalue"
                    placeholder="e.g. 250000"
                    class="form-input font-mono"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Primary Email:</label>
                  <input
                    type="email"
                    v-model="irrguar.primary_email"
                    placeholder="e.g. guarantor@example.com"
                    class="form-input"
                  />
                </div>
                <div>
                  <label class="form-label">Secondary Email:</label>
                  <input
                    type="email"
                    v-model="irrguar.secondary_email"
                    placeholder="e.g. alt_guar@example.com"
                    class="form-input"
                  />
                </div>
                <div>
                  <label class="form-label">Occupation/Position *:</label>
                  <input
                    type="text"
                    v-model="irrguar.occupation"
                    placeholder="e.g. MEDICAL DOCTOR, ACCOUNTANT"
                    @input="irrguar.occupation = (irrguar.occupation || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Tel/Mob. No *:</label>
                  <input
                    type="tel"
                    v-model="irrguar.mobileno"
                    placeholder="e.g. 0244123890"
                    class="form-input"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Alternate Tel/Mob. No:</label>
                  <input
                    type="tel"
                    v-model="irrguar.alternate_mobileno"
                    placeholder="e.g. 0550123890"
                    class="form-input"
                  />
                </div>
                <div>
                  <label class="form-label">Region *:</label>
                  <select v-model="irrguar.region_id" class="form-select" required>
                    <option value="" disabled>-- Select Region --</option>
                    <option v-for="r in regionList" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Business Address *:</label>
                  <input
                    type="text"
                    v-model="irrguar.businessaddr"
                    placeholder="e.g. KORLE BU HOSPITAL, ACCRA"
                    @input="irrguar.businessaddr = (irrguar.businessaddr || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Residential Address *:</label>
                  <input
                    type="text"
                    v-model="irrguar.residenceaddr"
                    placeholder="e.g. PLOT 15, EAST LEGON HILLS, ACCRA"
                    @input="irrguar.residenceaddr = (irrguar.residenceaddr || '').toUpperCase()"
                    class="form-input uppercase"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Ghana Card Number *:</label>
                  <input
                    type="text"
                    v-model="irrguar.ghcard"
                    placeholder="GHA-000000000-0"
                    @input="irrguar.ghcard = (irrguar.ghcard || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Ghana Digital Address *:</label>
                  <input
                    type="text"
                    v-model="irrguar.digitaladdr"
                    placeholder="e.g. GD-192-8821"
                    @input="irrguar.digitaladdr = (irrguar.digitaladdr || '').toUpperCase()"
                    class="form-input uppercase font-mono"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Relationship with Applicant *:</label>
                  <select v-model="irrguar.relation" class="form-select" required>
                    <option value="" disabled>-- Select Relation --</option>
                    <option v-for="r in relationList" :key="r.id" :value="r.id">{{ r.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Duration of Relationship (Min 3 years) *:</label>
                  <input
                    type="number"
                    v-model.number="irrguar.relationyear"
                    min="3"
                    placeholder="Three (3) years minimum"
                    class="form-input"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">Secondary Contact name:</label>
                  <input
                    type="text"
                    v-model="irrguar.secondary_contact_person_name"
                    placeholder="e.g. SAMUEL ADDO"
                    @input="irrguar.secondary_contact_person_name = (irrguar.secondary_contact_person_name || '').toUpperCase()"
                    class="form-input uppercase"
                  />
                </div>
                <div>
                  <label class="form-label">Secondary Contact Number:</label>
                  <input
                    type="tel"
                    v-model="irrguar.secondary_contact_person_number"
                    placeholder="e.g. 0244009988"
                    class="form-input"
                  />
                </div>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600 leading-relaxed">
                The guarantor should also submit with evidence of citizenship and identity any of the following: Driver's License /Passport/Voter's ID card/ Employment ID Card. The guarantor must submit in person to the Head Office of the Company or any other office authorized to receive such forms with Two (2) current Passport picture.
              </div>
            </div>
          </div>

          <!-- ─── PAGE 2: Declaration ─── -->
          <div v-show="irrguarstep == 2" class="section-card animate-fade-in">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>Declaration</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4 text-xs text-slate-700 leading-relaxed font-medium">
              <p>
                I <strong>{{ irrguar.guarname || 'Guarantor' }}</strong> with telephone number(s) <strong>{{ irrguar.mobileno || '-' }}</strong> <strong v-if="irrguar.alternate_mobileno"> / {{ irrguar.alternate_mobileno }}</strong>, an employee of 
                <input
                  type="text"
                  v-model="irrguar.company"
                  placeholder="Employer / Company Name"
                  @input="irrguar.company = (irrguar.company || '').toUpperCase()"
                  class="form-input uppercase inline-block w-48 mx-1 py-1 px-2 text-xs font-bold"
                  required
                />
                and presently residing at <strong>{{ irrguar.residenceaddr || '-' }}</strong> in the <strong>{{ findRegionName(irrguar.region_id) }}</strong> region of the Republic of Ghana voluntarily presents myself as a guarantor.
              </p>

              <p>
                I understand and verily believe same to be true that you have employed <strong>{{ (emp.firstname + ' ' + emp.surname).trim() || 'the Candidate' }}</strong> as a <strong>{{ emp.joiningposition || 'Staff' }}</strong> in your organization and clearly understand my obligations thereof.
              </p>

              <p>
                I have known the above named person for <strong>{{ irrguar.relationyear || 3 }}</strong> years and do hereby consent to standing in as a guarantor to secure the organization against any losses that may accrue due to his/her actions, omissions, fraud, dishonesty, malfeasance or other(s) as the case may be to the tune of 
                <input
                  type="number"
                  v-model.number="irrguar.guaramount"
                  class="form-input inline-block w-28 mx-1 py-1 px-2 text-xs font-mono font-bold"
                  required
                />
                (In words) <strong>{{ numberToWords(irrguar.guaramount || 5000) }}</strong> Ghana Cedis.
              </p>

              <p>
                Any claims made under this guarantee must be sent (without limitations via any reachable means) and received by me accompanied by a signed statement indicating that the abovementioned employee has failed in fulfilling his / her obligations which has ensued losses to your organization.
              </p>

              <p>
                Such statement and claim shall be conclusive evidence of the amount being claimed under this guarantee and as such I and the above named employee waive all rights against the organization of any suits or actions whatsoever and howsoever.
              </p>
            </div>
          </div>

          <!-- ─── PAGE 3: Signature & Witnesses (MIN 2) ─── -->
          <div v-show="irrguarstep == 3" class="space-y-6 animate-fade-in">
            
            <!-- Guarantor Signature Section -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>Signature of Guarantor</span>
                </div>
                <button
                  type="button"
                  @click="clearIrrGuarSignature"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-refresh text-[10px]"></i>
                  <span>Clear</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-3">
                <p class="text-xs text-slate-800 font-bold m-0">
                  Signed on this DAY OF <strong>{{ todayString }}</strong>
                </p>

                <div class="relative bg-white rounded-xl border-2 border-slate-300 overflow-hidden touch-none shadow-inner">
                  <canvas
                    ref="sigIrrCanvas"
                    class="w-full h-40 bg-white cursor-crosshair block"
                    @mousedown="startDrawSigIrr"
                    @mousemove="drawSigIrr"
                    @mouseup="stopDrawSigIrr"
                    @mouseleave="stopDrawSigIrr"
                    @touchstart.prevent="touchStartSigIrr"
                    @touchmove.prevent="touchMoveSigIrr"
                    @touchend.prevent="touchEndSigIrr"
                  ></canvas>
                  <div class="absolute bottom-2 left-4 right-4 border-b border-dashed border-slate-300 pointer-events-none flex justify-between text-[10px] text-slate-400 font-bold pb-0.5">
                    <span>Guarantor Continuing Guarantee Signature</span>
                    <span>Digital Pad</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- WITNESSES (MIN 2) -->
            <div class="section-card">
              <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                  <span class="accent-bar"></span>
                  <span>WITNESSES (MIN 2)</span>
                </div>
                <button
                  type="button"
                  @click="addIrrWitness"
                  class="px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                >
                  <i class="pi pi-plus text-[10px]"></i>
                  <span>Add Witness</span>
                </button>
              </div>

              <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div v-for="(witness, ind) in irrguarwitnesses" :key="ind" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                  <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <span class="text-xs font-black uppercase text-[#1A237E]">Witness {{ ind + 1 }}</span>
                    <button
                      v-if="irrguarwitnesses.length > 2"
                      type="button"
                      @click="removeIrrWitness(ind)"
                      class="text-xs text-red-600 font-bold hover:underline cursor-pointer"
                    >
                      Remove
                    </button>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label class="form-label">Name *:</label>
                      <input
                        type="text"
                        v-model="witness.name"
                        placeholder="e.g. KOFI OSEI"
                        @input="witness.name = (witness.name || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Residential address *:</label>
                      <input
                        type="text"
                        v-model="witness.address"
                        placeholder="e.g. HSE NO. 44, ACHIMOTA, ACCRA"
                        @input="witness.address = (witness.address || '').toUpperCase()"
                        class="form-input uppercase"
                        required
                      />
                    </div>
                    <div>
                      <label class="form-label">Phone No *:</label>
                      <input
                        type="tel"
                        v-model="witness.phoneno"
                        placeholder="e.g. 0244112233"
                        class="form-input"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- ======================================================== -->
        <!-- TAB 4: CHECKLIST & COMPLIANCE (Word-for-Word Parity)     -->
        <!-- ======================================================== -->
        <div v-show="maintab == 4" class="space-y-6 animate-fade-in">
          
          <!-- Section: Checklist & Compliance Verification -->
          <div class="section-card">
            <div class="section-header">
              <span class="accent-bar"></span>
              <span>Employee Onboarding Checklist &amp; Compliance Verification</span>
            </div>

            <div class="p-5 sm:p-6 bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-xs space-y-3">
              <p class="text-xs text-slate-500 font-medium mb-3">Track verification status and required compliance document uploads</p>

              <div class="divide-y divide-slate-200 border border-slate-200 rounded-xl overflow-hidden text-xs">
                
                <!-- 1. Profile Form Filled -->
                <div class="p-3.5 flex items-center justify-between bg-slate-50">
                  <span class="font-bold text-slate-800">Profile Form Filled</span>
                  <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center gap-1">
                    <i class="pi pi-check text-[10px]"></i> Done
                  </span>
                </div>

                <!-- 2. 2 Passport size pictures -->
                <div class="p-3.5 flex items-center justify-between">
                  <span class="font-bold text-slate-800">2 Passport size pictures</span>
                  <span v-if="emp.profilepicture" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center gap-1">
                    <i class="pi pi-check text-[10px]"></i> Done
                  </span>
                  <span v-else class="text-slate-400 italic">Upload in Personal Info</span>
                </div>

                <!-- 3. Application Letter -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <span class="font-bold text-slate-800">Application Letter</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.appletter ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'appletter')" class="hidden" />
                    </label>
                    <span v-if="documents.appletter" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 4. Appointment Letter -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50">
                  <span class="font-bold text-slate-800">Appointment Letter</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.appointmentletter ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'appointmentletter')" class="hidden" />
                    </label>
                    <span v-if="documents.appointmentletter" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 5. Probation Confirmation Detail -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <span class="font-bold text-slate-800">Probation Confirmation Detail</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.probationconf ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'probationconf')" class="hidden" />
                    </label>
                    <span v-if="documents.probationconf" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 6. CV -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50">
                  <span class="font-bold text-slate-800">CV</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.resume_cv ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'resume_cv')" class="hidden" />
                    </label>
                    <span v-if="documents.resume_cv" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 7. Petra Trust form filled -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <span class="font-bold text-slate-800">Petra Trust form filled</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.petratrust ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'petratrust')" class="hidden" />
                    </label>
                    <span v-if="documents.petratrust" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 8. National Identification Card 2 Coloured Copies (GHANA CARD) -->
                <div class="p-3.5 flex items-center justify-between bg-slate-50">
                  <span class="font-bold text-slate-800">National Identification Card 2 Coloured Copies (GHANA CARD)</span>
                  <span v-if="emp.ghcardfile" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center gap-1">
                    <i class="pi pi-check text-[10px]"></i> Done
                  </span>
                  <span v-else class="text-slate-400 italic">Uploaded in Page 1</span>
                </div>

                <!-- 9. Guarantor Forms (Guarantor ID Card, etc.) -->
                <div class="p-3.5 flex items-center justify-between">
                  <span class="font-bold text-slate-800">Guarantor Forms (Guarantor ID Card, etc.)</span>
                  <span v-if="irrguar.guarname" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center gap-1">
                    <i class="pi pi-check text-[10px]"></i> Done
                  </span>
                  <button v-else type="button" @click="maintab = 3; irrguarstep = 1" class="text-xs text-[#1A237E] font-bold hover:underline cursor-pointer">
                    Fill Now
                  </button>
                </div>

                <!-- 10. Bank / Social Security Fund -->
                <div class="p-3.5 flex items-center justify-between bg-slate-50">
                  <span class="font-bold text-slate-800">Bank / Social Security Fund</span>
                  <span v-if="banksocial.bankname && banksocial.accountnumber" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[11px] flex items-center gap-1">
                    <i class="pi pi-check text-[10px]"></i> Done
                  </span>
                  <button v-else type="button" @click="maintab = 2" class="text-xs text-[#1A237E] font-bold hover:underline cursor-pointer">
                    Fill Now
                  </button>
                </div>

                <!-- 11. NHIS Card Copy -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <span class="font-bold text-slate-800">NHIS Card Copy</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.nhis ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'nhis')" class="hidden" />
                    </label>
                    <span v-if="documents.nhis" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 12. Birth Certificate Copy -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50">
                  <span class="font-bold text-slate-800">Birth Certificate Copy</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.birthcert ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'birthcert')" class="hidden" />
                    </label>
                    <span v-if="documents.birthcert" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 13. Police Clearance form for Drivers -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <span class="font-bold text-slate-800">
                    Police Clearance form for Drivers (exclusively for drivers, security officers, Shop Managers, Sales Managers/Executives.)
                  </span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.pclearanceform ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'pclearanceform')" class="hidden" />
                    </label>
                    <span v-if="documents.pclearanceform" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

                <!-- 14. SSNIT Card Copy -->
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50">
                  <span class="font-bold text-slate-800">SSNIT Card Copy</span>
                  <div class="flex items-center gap-2">
                    <label class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-1 shadow-xs">
                      <i class="pi pi-upload text-xs"></i>
                      <span>{{ documents.ssnit ? 'Change File' : 'Upload' }}</span>
                      <input type="file" accept="image/*,application/pdf" @change="e => handleDocUpload(e, 'ssnit')" class="hidden" />
                    </label>
                    <span v-if="documents.ssnit" class="text-emerald-600 font-bold flex items-center gap-1">
                      <i class="pi pi-check"></i> Done
                    </span>
                  </div>
                </div>

              </div>

              <!-- Final Declaration Checkbox -->
              <div class="pt-4">
                <label class="flex items-start gap-3 cursor-pointer select-none p-4 rounded-xl border border-slate-300 hover:bg-slate-50 transition">
                  <input
                    type="checkbox"
                    v-model="agreedDeclaration"
                    class="mt-0.5 h-5 w-5 rounded text-[#1A237E] focus:ring-[#1A237E] border-slate-300 cursor-pointer"
                    required
                  />
                  <span class="text-xs font-bold text-slate-800 leading-snug">
                    I {{ (emp.firstname + ' ' + emp.surname).trim() || 'Candidate' }} declare that the information provided above is true. In case of false declaration, the appropriate action as per company's policy will be taken. *
                  </span>
                </label>
              </div>

            </div>
          </div>
        </div>

        <!-- ======================================================== -->
        <!-- DESKTOP ACTION BAR                                       -->
        <!-- ======================================================== -->
        <div class="hidden sm:flex items-center justify-between pt-6 mt-6 border-t border-slate-200">
          <button
            v-if="canGoBack"
            type="button"
            @click="handleBack"
            class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition flex items-center gap-2 cursor-pointer"
          >
            <i class="pi pi-arrow-left text-xs"></i>
            <span>Previous</span>
          </button>
          <div v-else></div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="saveDraftToStorage(true)"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 font-semibold text-xs transition cursor-pointer"
            >
              Save Draft
            </button>

            <button
              v-if="!isLastStep"
              type="button"
              @click="handleNext"
              class="px-7 py-2.5 rounded-xl bg-[#1A237E] hover:bg-[#121858] text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-indigo-100 cursor-pointer"
            >
              <span>Continue</span>
              <i class="pi pi-arrow-right text-xs"></i>
            </button>

            <button
              v-else
              type="submit"
              :disabled="submitting"
              class="px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition flex items-center gap-2 shadow-lg shadow-emerald-200 disabled:opacity-50 cursor-pointer"
            >
              <i v-if="submitting" class="pi pi-spin pi-spinner text-xs"></i>
              <i v-else class="pi pi-check text-xs"></i>
              <span>{{ submitting ? 'Submitting Application...' : 'Submit Application' }}</span>
            </button>
          </div>
        </div>
      </form>
    </main>

    <!-- ======================================================== -->
    <!-- STICKY MOBILE BOTTOM BAR (Smartphone-First UX)           -->
    <!-- ======================================================== -->
    <div v-if="!submittedSuccess" class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur border-t border-slate-200 p-2.5 shadow-2xl">
      <div class="flex items-center justify-between gap-2 max-w-lg mx-auto">
        <button
          v-if="canGoBack"
          type="button"
          @click="handleBack"
          class="h-11 px-3.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center gap-1 active:bg-slate-100 cursor-pointer"
        >
          <i class="pi pi-arrow-left text-xs"></i>
          <span>Back</span>
        </button>
        <button
          v-else
          type="button"
          @click="saveDraftToStorage(true)"
          class="h-11 px-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs active:bg-slate-100 cursor-pointer"
        >
          Save
        </button>

        <div class="text-center px-1">
          <span class="text-[11px] font-black text-[#1A237E] block truncate max-w-[140px]">
            {{ maintab === 1 ? `Page ${perinfostep} of 4` : (maintab === 3 ? `Guarantee ${irrguarstep}/3` : currentTabName) }}
          </span>
        </div>

        <button
          v-if="!isLastStep"
          type="button"
          @click="handleNext"
          class="h-11 px-5 rounded-xl bg-[#1A237E] text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-md shadow-indigo-200 active:bg-[#121858] cursor-pointer"
        >
          <span>Continue</span>
          <i class="pi pi-arrow-right text-xs"></i>
        </button>

        <button
          v-else
          type="button"
          @click="handleFormAction"
          :disabled="submitting"
          class="h-11 px-5 rounded-xl bg-emerald-600 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-200 disabled:opacity-50 active:bg-emerald-700 cursor-pointer"
        >
          <i v-if="submitting" class="pi pi-spin pi-spinner text-xs"></i>
          <span>{{ submitting ? 'Submitting...' : 'Submit Form' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import {
  calculateAge,
  numberToWords,
  aToday
} from '@/helpers/essential'
import {
  companies as masterCompanies,
  depts as masterDepts,
  branchs as masterBranches,
  regions as masterRegions,
  banks as masterBanks,
  conttypes as masterConttypes,
  mstatus as masterMstatus,
  bankaccounttype as masterBankaccounttypes,
  relations as masterRelations,
  familyrelation as masterFamilyrelations,
  countries as masterCountries,
  qualtypes as masterQualtypes,
  idtypes as masterIdtypes
} from '@/data/masterdata'

// Master data lists
const companyList = ref(masterCompanies || [])
const deptList = ref(masterDepts || [])
const branchList = ref(masterBranches || [])
const regionList = ref(masterRegions || [])
const bankList = ref(masterBanks || [])
const bankAccTypes = ref(masterBankaccounttypes || [{ id: 1, name: 'CURRENT' }, { id: 2, name: 'SAVINGS' }])
const relationList = ref(masterRelations || [])
const familyRelList = ref(masterFamilyrelations || [])
const countryList = ref(masterCountries || [])
const qualList = ref(masterQualtypes || [])
const idTypeList = ref(masterIdtypes || [])
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

// Today String for Signatures
const todayString = computed(() => aToday(new Date()))

// Tabs state
const maintab = ref(1) // 1 = Form, 2 = Bank, 3 = Guarantee, 4 = Checklist
const perinfostep = ref(1) // 1, 2, 3, 4
const irrguarstep = ref(1) // 1, 2, 3

const profileInputRef = ref(null)
const triggerProfileUpload = () => {
  if (profileInputRef.value) {
    profileInputRef.value.click()
  }
}

// 1. Employee details state
const emp = reactive({
  joining_company_id: 3, // Melcom default
  contracttype: 'contract',
  joining_branch_id: 1,
  joining_dept_id: 34, // Retail
  joiningposition: '',
  joiningdate: new Date().toISOString().slice(0, 10),
  firstname: '',
  middlename: '',
  surname: '',
  citizenship: 'GH',
  idtype: null,
  ghcardno: '',
  ghcardfile: null,
  raddress: '',
  daddress: '',
  hometown: '',
  hdaddress: '',
  mobileno: '',
  altnumber: '',
  email: '',
  gender: 'M',
  socialsecurityno: '',
  dob: '',
  maritalstatus: 5,
  fathersname: '',
  mothersname: '',
  relativeinorg: 0,
  relative_name: '',
  relative_relation: null,
  anyotherinfo: '',
  profilepicture: null,
  signature: null,
  guarsignature: null
})

// Spouses & Children
const wives = ref([])
const haschildren = ref(false)
const childrens = ref([])

// Melcom Prior Employment
const iscurwork = ref(false)
const curwork = reactive({
  title: '',
  employeeid: '',
  doj: '',
  dept_id: null,
  branch_id: null,
  region_id: null
})

// Union
const isunion = ref(false)
const unioninfo = reactive({
  union_name: '',
  union_regdate: '',
  union_number: ''
})

// Work Permit
const wpermit = reactive({
  renewalno: null,
  passedate: '',
  idate: '',
  edate: '',
  number: ''
})

// Driver's License
const dlicense = reactive({
  name: '',
  licenseno: '',
  idate: '',
  edate: '',
  ref: '',
  rdate: '',
  files: []
})

// Emergency Contacts
const econts = ref([
  {
    fullname: '',
    relation: 1,
    workaddress: '',
    ghcardno: '',
    mobileno: '',
    altnumber: '',
    isnextofkin: 1
  }
])

// Social Contacts
const soccont = reactive({
  churchmemberloc: '',
  pastor: '',
  memduration: null,
  mobileno: '',
  ghcardno: '',
  closestfriend: '',
  contactsphone: '',
  email: '',
  digitaladdress: '',
  workaddress: ''
})

// Education
const edus = ref([
  {
    educqualtype: 2, // WASSCE / SSCE default
    educqual: '',
    to: ''
  }
])

// Work Experience
const workexps = ref([])

// References
const refs = ref([
  {
    name: '',
    companyname: '',
    designation: '',
    contactno: '',
    email: ''
  }
])

// Nominee
const nominee = reactive({
  name: '',
  relation: 1,
  mobile: ''
})

// 2. Bank Details state
const banksocial = reactive({
  bankname: '',
  bankbranch: '',
  accountname: '',
  accounttype: 1, // CURRENT
  accountnumber: '',
  socialfundnumber: '',
  petratrustnumber: ''
})

// 3. Irrevocable Continuing Guarantee state
const irrguar = reactive({
  guarname: '',
  ssfno: '',
  annualincome: null,
  propertyvalue: null,
  primary_email: '',
  secondary_email: '',
  occupation: '',
  mobileno: '',
  alternate_mobileno: '',
  region_id: 7, // Greater Accra
  businessaddr: '',
  residenceaddr: '',
  ghcard: '',
  digitaladdr: '',
  relation: 1,
  relationyear: 3,
  secondary_contact_person_name: '',
  secondary_contact_person_number: '',
  company: '',
  guaramount: 5000,
  signature: null
})

const irrguarwitnesses = ref([
  { name: '', address: '', phoneno: '' },
  { name: '', address: '', phoneno: '' }
])

// 4. Checklist Documents state
const documents = reactive({
  resume_cv: null,
  appletter: null,
  appointmentletter: null,
  probationconf: null,
  petratrust: null,
  nhis: null,
  birthcert: null,
  pclearanceform: null,
  ssnit: null
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
const draftSavedAt = ref('')
const copyStatusText = ref('Copy')

// Signatures Canvas refs
const sig1Canvas = ref(null)
const sigGuarCanvas = ref(null)
const sigIrrCanvas = ref(null)

let sig1Ctx = null
let sigGuarCtx = null
let sigIrrCtx = null

let isDrawing1 = false
let isDrawingGuar = false
let isDrawingIrr = false

// Age calculation
const computedAge = computed(() => {
  if (!emp.dob) return null
  return calculateAge(emp.dob)
})

const maxDobDate = computed(() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() - 15)
  return d.toISOString().slice(0, 10)
})

// Navigation helpers
const canGoBack = computed(() => {
  return maintab.value > 1 || perinfostep.value > 1 || irrguarstep.value > 1
})

const isLastStep = computed(() => {
  return maintab.value === 4
})

const currentTabName = computed(() => {
  if (maintab.value === 1) return `Form Page ${perinfostep.value}/4`
  if (maintab.value === 2) return 'Bank Details'
  if (maintab.value === 3) return `Guarantee Page ${irrguarstep.value}/3`
  return 'Compliance & Submit'
})

// Handlers for Navigation
const handleBack = () => {
  if (maintab.value === 1) {
    if (perinfostep.value > 1) {
      perinfostep.value--
    }
  } else if (maintab.value === 2) {
    maintab.value = 1
    perinfostep.value = 4
  } else if (maintab.value === 3) {
    if (irrguarstep.value > 1) {
      irrguarstep.value--
    } else {
      maintab.value = 2
    }
  } else if (maintab.value === 4) {
    maintab.value = 3
    irrguarstep.value = 3
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
  saveDraftToStorage()
}

const handleNext = () => {
  if (maintab.value === 1) {
    if (validatePage(perinfostep.value)) {
      if (perinfostep.value < 4) {
        perinfostep.value++
      } else {
        maintab.value = 2
      }
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
      if (perinfostep.value === 4) nextTick(initPage4Canvases)
    }
  } else if (maintab.value === 2) {
    if (validateBank()) {
      maintab.value = 3
      irrguarstep.value = 1
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
    }
  } else if (maintab.value === 3) {
    if (validateGuarantee(irrguarstep.value)) {
      if (irrguarstep.value < 3) {
        irrguarstep.value++
      } else {
        maintab.value = 4
      }
      window.scrollTo({ top: 0, behavior: 'smooth' })
      saveDraftToStorage()
      if (irrguarstep.value === 3) nextTick(initIrrCanvas)
    }
  }
}

const handleFormAction = () => {
  if (!isLastStep.value) {
    handleNext()
  } else {
    submitApplication()
  }
}

// Validations
const validatePage = (step) => {
  const errors = []
  if (step === 1) {
    if (!emp.joining_company_id) errors.push('Please select a Joining Company.')
    if (!emp.joining_branch_id) errors.push('Please select your assigned Location.')
    if (!emp.joining_dept_id) errors.push('Please select your Department.')
    if (!emp.joiningposition || !emp.joiningposition.trim()) errors.push('Please enter your Job Title / Position.')
    if (!emp.firstname || !emp.firstname.trim()) errors.push('Please enter your First Name.')
    if (!emp.surname || !emp.surname.trim()) errors.push('Please enter your SurName.')
    if (!emp.dob) errors.push('Please select your Date of Birth.')
    if (!emp.ghcardno || emp.ghcardno.length < 8) errors.push('Please enter a valid Ghana Card / National ID Number.')
    if (!emp.mobileno || emp.mobileno.length < 8) errors.push('Please enter a valid Mobile Phone Number.')
    if (!emp.raddress || !emp.raddress.trim()) errors.push('Please enter your Residential Address.')
    if (!emp.daddress || !emp.daddress.trim()) errors.push('Please enter your Residential Ghana Digital Address.')
    if (!emp.hometown || !emp.hometown.trim()) errors.push('Please enter your Permanent Home Town/Region.')
    if (!emp.hdaddress || !emp.hdaddress.trim()) errors.push('Please enter your Permanent Ghana Digital Address.')
    if (!emp.fathersname || !emp.fathersname.trim()) errors.push("Please enter Father's Name.")
    if (!emp.mothersname || !emp.mothersname.trim()) errors.push("Please enter Mother's Name.")
  } else if (step === 2) {
    if (!econts.value[0].fullname || !econts.value[0].fullname.trim()) errors.push('Please enter Emergency Relative Full Name.')
    if (!econts.value[0].mobileno || econts.value[0].mobileno.length < 8) errors.push('Please enter Emergency Relative Mobile Phone Number.')
  } else if (step === 3) {
    if (edus.value.length === 0 || !edus.value[0].educqual || !edus.value[0].educqual.trim()) {
      errors.push('Please provide at least one school / qualification under Educational Background.')
    }
    if (refs.value.length === 0 || !refs.value[0].name || !refs.value[0].name.trim()) {
      errors.push('Please provide at least one character / professional reference.')
    }
    if (!nominee.name || !nominee.name.trim()) {
      errors.push('Please provide Nominee (Next of Kin) Full Name.')
    }
    if (!nominee.mobile || nominee.mobile.length < 8) {
      errors.push('Please provide Nominee Mobile Number.')
    }
  } else if (step === 4) {
    saveSig1Data()
    saveSigGuarData()
    if (!emp.signature) {
      errors.push('Please provide your Employee Signature on Page 4.')
    }
  }

  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Required Fields',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#1A237E'
    })
    return false
  }
  return true
}

const validateBank = () => {
  const errors = []
  if (!banksocial.bankname) errors.push('Please select your Bank Name.')
  if (!banksocial.bankbranch || !banksocial.bankbranch.trim()) errors.push('Please enter your Bank Branch.')
  if (!banksocial.accountname || !banksocial.accountname.trim()) errors.push('Please enter your Bank Account Name.')
  if (!banksocial.accountnumber || !banksocial.accountnumber.trim()) errors.push('Please enter your Bank Account Number.')
  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Bank Details',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#1A237E'
    })
    return false
  }
  return true
}

const validateGuarantee = (step) => {
  const errors = []
  if (step === 1) {
    if (!irrguar.guarname || !irrguar.guarname.trim()) errors.push('Please enter Name of Guarantor.')
    if (!irrguar.ssfno || !irrguar.ssfno.trim()) errors.push('Please enter Guarantor SSF No.')
    if (!irrguar.annualincome) errors.push('Please enter Guarantor Annual Income.')
    if (!irrguar.propertyvalue) errors.push('Please enter Value of Landed Property.')
    if (!irrguar.occupation || !irrguar.occupation.trim()) errors.push('Please enter Guarantor Occupation/Position.')
    if (!irrguar.mobileno || irrguar.mobileno.length < 8) errors.push('Please enter Guarantor Tel/Mob. No.')
    if (!irrguar.businessaddr || !irrguar.businessaddr.trim()) errors.push('Please enter Guarantor Business Address.')
    if (!irrguar.residenceaddr || !irrguar.residenceaddr.trim()) errors.push('Please enter Guarantor Residential Address.')
    if (!irrguar.ghcard || !irrguar.ghcard.trim()) errors.push('Please enter Guarantor Ghana Card Number.')
    if (!irrguar.digitaladdr || !irrguar.digitaladdr.trim()) errors.push('Please enter Guarantor Ghana Digital Address.')
  } else if (step === 2) {
    if (!irrguar.company || !irrguar.company.trim()) errors.push('Please enter the Employer / Company name of your Guarantor.')
    if (!irrguar.guaramount) errors.push('Please enter Guarantee Amount.')
  } else if (step === 3) {
    saveSigIrrData()
    if (!irrguar.signature) errors.push('Please provide Guarantor Signature.')
    if (!irrguarwitnesses.value[0].name || !irrguarwitnesses.value[0].phoneno) {
      errors.push('Please provide Witness 1 Name and Phone Number.')
    }
    if (!irrguarwitnesses.value[1].name || !irrguarwitnesses.value[1].phoneno) {
      errors.push('Please provide Witness 2 Name and Phone Number.')
    }
  }

  if (errors.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Missing Guarantee Details',
      html: `<div class="text-left text-sm text-slate-700"><ul>${errors.map(e => `<li class="py-1">• ${e}</li>`).join('')}</ul></div>`,
      confirmButtonColor: '#1A237E'
    })
    return false
  }
  return true
}

// Helpers
const onNameChange = () => {
  emp.firstname = (emp.firstname || '').toUpperCase()
  emp.surname = (emp.surname || '').toUpperCase()
  if (!banksocial.accountname || banksocial.accountname === `${emp.firstname} ${emp.surname}`.trim()) {
    banksocial.accountname = `${emp.firstname} ${emp.surname}`.trim()
  }
}

const formatGhCard = () => {
  let val = (emp.ghcardno || '').toUpperCase().replace(/[^A-Z0-9]/g, '')
  if (val.startsWith('GHA')) val = val.substring(3)
  let formatted = 'GHA-'
  if (val.length > 0) formatted += val.substring(0, 9)
  if (val.length > 9) formatted += '-' + val.substring(9, 10)
  emp.ghcardno = formatted
}

const getCompanyName = (id) => {
  const c = companyList.value.find(item => item.id === Number(id))
  return c ? c.name : 'Melcom'
}

const getBranchName = (id) => {
  const b = branchList.value.find(item => item.id === Number(id))
  return b ? b.name : 'Branch'
}

const findRegionName = (id) => {
  const r = regionList.value.find(item => item.id === Number(id))
  return r ? r.name : 'GREATER ACCRA'
}

// Dynamic List Actions
const addSpouse = () => wives.value.push({ name: '', occupation: '' })
const removeSpouse = (i) => wives.value.splice(i, 1)

const addChild = () => childrens.value.push({ name: '', age: null })
const removeChild = (i) => childrens.value.splice(i, 1)

const addEcont = () => econts.value.push({ fullname: '', relation: 1, workaddress: '', ghcardno: '', mobileno: '', altnumber: '', isnextofkin: 0 })
const removeEcont = (i) => econts.value.splice(i, 1)

const addEdu = () => edus.value.push({ educqualtype: 2, educqual: '', to: '' })
const removeEdu = (i) => edus.value.splice(i, 1)

const addWorkExp = () => workexps.value.push({ orgname: '', postheld: '', from: '', to: '', reasonforleaving: '', salary: '' })
const removeWorkExp = (i) => workexps.value.splice(i, 1)

const addRef = () => refs.value.push({ name: '', companyname: '', designation: '', contactno: '', email: '' })
const removeRef = (i) => refs.value.splice(i, 1)

const addIrrWitness = () => irrguarwitnesses.value.push({ name: '', address: '', phoneno: '' })
const removeIrrWitness = (i) => irrguarwitnesses.value.splice(i, 1)

// File upload helpers
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

const handleFileField = (e, field) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (event) => {
    emp[field] = event.target.result
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

// ─── Canvas Signatures ───
const setupCanvasContext = (canvasRef) => {
  const canvas = canvasRef.value
  if (!canvas) return null
  const ratio = Math.max(window.devicePixelRatio || 1, 1)
  const rect = canvas.getBoundingClientRect()
  canvas.width = rect.width * ratio
  canvas.height = rect.height * ratio
  const ctx = canvas.getContext('2d')
  ctx.scale(ratio, ratio)
  ctx.strokeStyle = '#0f172a'
  ctx.lineWidth = 2.5
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  return ctx
}

const initPage4Canvases = () => {
  sig1Ctx = setupCanvasContext(sig1Canvas)
  sigGuarCtx = setupCanvasContext(sigGuarCanvas)

  if (emp.signature && sig1Canvas.value && sig1Ctx) {
    const img = new Image()
    img.onload = () => sig1Ctx.drawImage(img, 0, 0, sig1Canvas.value.clientWidth, sig1Canvas.value.clientHeight)
    img.src = emp.signature
  }
  if (emp.guarsignature && sigGuarCanvas.value && sigGuarCtx) {
    const img = new Image()
    img.onload = () => sigGuarCtx.drawImage(img, 0, 0, sigGuarCanvas.value.clientWidth, sigGuarCanvas.value.clientHeight)
    img.src = emp.guarsignature
  }
}

const initIrrCanvas = () => {
  sigIrrCtx = setupCanvasContext(sigIrrCanvas)
  if (irrguar.signature && sigIrrCanvas.value && sigIrrCtx) {
    const img = new Image()
    img.onload = () => sigIrrCtx.drawImage(img, 0, 0, sigIrrCanvas.value.clientWidth, sigIrrCanvas.value.clientHeight)
    img.src = irrguar.signature
  }
}

// Sig 1 (Employee)
const startDrawSig1 = (e) => {
  if (!sig1Ctx || !sig1Canvas.value) return
  isDrawing1 = true
  const rect = sig1Canvas.value.getBoundingClientRect()
  sig1Ctx.beginPath()
  sig1Ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top)
}
const drawSig1 = (e) => {
  if (!isDrawing1 || !sig1Ctx || !sig1Canvas.value) return
  const rect = sig1Canvas.value.getBoundingClientRect()
  sig1Ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top)
  sig1Ctx.stroke()
}
const stopDrawSig1 = () => {
  isDrawing1 = false
  saveSig1Data()
}
const touchStartSig1 = (e) => {
  if (!sig1Ctx || !sig1Canvas.value || !e.touches[0]) return
  isDrawing1 = true
  const rect = sig1Canvas.value.getBoundingClientRect()
  sig1Ctx.beginPath()
  sig1Ctx.moveTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
}
const touchMoveSig1 = (e) => {
  if (!isDrawing1 || !sig1Ctx || !sig1Canvas.value || !e.touches[0]) return
  const rect = sig1Canvas.value.getBoundingClientRect()
  sig1Ctx.lineTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
  sig1Ctx.stroke()
}
const touchEndSig1 = () => {
  isDrawing1 = false
  saveSig1Data()
}
const saveSig1Data = () => {
  if (sig1Canvas.value) emp.signature = sig1Canvas.value.toDataURL('image/png')
}
const clearEmployeeSignature = () => {
  if (sig1Canvas.value && sig1Ctx) {
    sig1Ctx.clearRect(0, 0, sig1Canvas.value.width, sig1Canvas.value.height)
    emp.signature = null
  }
}

// Sig Guar
const startDrawSigGuar = (e) => {
  if (!sigGuarCtx || !sigGuarCanvas.value) return
  isDrawingGuar = true
  const rect = sigGuarCanvas.value.getBoundingClientRect()
  sigGuarCtx.beginPath()
  sigGuarCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top)
}
const drawSigGuar = (e) => {
  if (!isDrawingGuar || !sigGuarCtx || !sigGuarCanvas.value) return
  const rect = sigGuarCanvas.value.getBoundingClientRect()
  sigGuarCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top)
  sigGuarCtx.stroke()
}
const stopDrawSigGuar = () => {
  isDrawingGuar = false
  saveSigGuarData()
}
const touchStartSigGuar = (e) => {
  if (!sigGuarCtx || !sigGuarCanvas.value || !e.touches[0]) return
  isDrawingGuar = true
  const rect = sigGuarCanvas.value.getBoundingClientRect()
  sigGuarCtx.beginPath()
  sigGuarCtx.moveTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
}
const touchMoveSigGuar = (e) => {
  if (!isDrawingGuar || !sigGuarCtx || !sigGuarCanvas.value || !e.touches[0]) return
  const rect = sigGuarCanvas.value.getBoundingClientRect()
  sigGuarCtx.lineTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
  sigGuarCtx.stroke()
}
const touchEndSigGuar = () => {
  isDrawingGuar = false
  saveSigGuarData()
}
const saveSigGuarData = () => {
  if (sigGuarCanvas.value) emp.guarsignature = sigGuarCanvas.value.toDataURL('image/png')
}
const clearGuarSignature = () => {
  if (sigGuarCanvas.value && sigGuarCtx) {
    sigGuarCtx.clearRect(0, 0, sigGuarCanvas.value.width, sigGuarCanvas.value.height)
    emp.guarsignature = null
  }
}

// Sig Irr Guarantee
const startDrawSigIrr = (e) => {
  if (!sigIrrCtx || !sigIrrCanvas.value) return
  isDrawingIrr = true
  const rect = sigIrrCanvas.value.getBoundingClientRect()
  sigIrrCtx.beginPath()
  sigIrrCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top)
}
const drawSigIrr = (e) => {
  if (!isDrawingIrr || !sigIrrCtx || !sigIrrCanvas.value) return
  const rect = sigIrrCanvas.value.getBoundingClientRect()
  sigIrrCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top)
  sigIrrCtx.stroke()
}
const stopDrawSigIrr = () => {
  isDrawingIrr = false
  saveSigIrrData()
}
const touchStartSigIrr = (e) => {
  if (!sigIrrCtx || !sigIrrCanvas.value || !e.touches[0]) return
  isDrawingIrr = true
  const rect = sigIrrCanvas.value.getBoundingClientRect()
  sigIrrCtx.beginPath()
  sigIrrCtx.moveTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
}
const touchMoveSigIrr = (e) => {
  if (!isDrawingIrr || !sigIrrCtx || !sigIrrCanvas.value || !e.touches[0]) return
  const rect = sigIrrCanvas.value.getBoundingClientRect()
  sigIrrCtx.lineTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top)
  sigIrrCtx.stroke()
}
const touchEndSigIrr = () => {
  isDrawingIrr = false
  saveSigIrrData()
}
const saveSigIrrData = () => {
  if (sigIrrCanvas.value) irrguar.signature = sigIrrCanvas.value.toDataURL('image/png')
}
const clearIrrGuarSignature = () => {
  if (sigIrrCanvas.value && sigIrrCtx) {
    sigIrrCtx.clearRect(0, 0, sigIrrCanvas.value.width, sigIrrCanvas.value.height)
    irrguar.signature = null
  }
}

// Draft storage
const STORAGE_KEY = 'melcom_online_onboarding_draft_v2'

const saveDraftToStorage = (notify = false) => {
  try {
    saveSig1Data()
    saveSigGuarData()
    saveSigIrrData()

    const draft = {
      emp,
      wives: wives.value,
      haschildren: haschildren.value,
      childrens: childrens.value,
      iscurwork: iscurwork.value,
      curwork,
      isunion: isunion.value,
      unioninfo,
      wpermit,
      dlicense,
      econts: econts.value,
      soccont,
      edus: edus.value,
      workexps: workexps.value,
      refs: refs.value,
      nominee,
      banksocial,
      irrguar,
      irrguarwitnesses: irrguarwitnesses.value,
      documents,
      maintab: maintab.value,
      perinfostep: perinfostep.value,
      irrguarstep: irrguarstep.value,
      savedAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
    draftSavedAt.value = draft.savedAt

    if (notify) {
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
    console.warn('Draft save error:', err)
  }
}

const loadDraftFromStorage = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return
    const d = JSON.parse(raw)
    if (d.emp) Object.assign(emp, d.emp)
    if (d.wives) wives.value = d.wives
    if (d.haschildren !== undefined) haschildren.value = d.haschildren
    if (d.childrens) childrens.value = d.childrens
    if (d.iscurwork !== undefined) iscurwork.value = d.iscurwork
    if (d.curwork) Object.assign(curwork, d.curwork)
    if (d.isunion !== undefined) isunion.value = d.isunion
    if (d.unioninfo) Object.assign(unioninfo, d.unioninfo)
    if (d.wpermit) Object.assign(wpermit, d.wpermit)
    if (d.dlicense) Object.assign(dlicense, d.dlicense)
    if (d.econts) econts.value = d.econts
    if (d.soccont) Object.assign(soccont, d.soccont)
    if (d.edus) edus.value = d.edus
    if (d.workexps) workexps.value = d.workexps
    if (d.refs) refs.value = d.refs
    if (d.nominee) Object.assign(nominee, d.nominee)
    if (d.banksocial) Object.assign(banksocial, d.banksocial)
    if (d.irrguar) Object.assign(irrguar, d.irrguar)
    if (d.irrguarwitnesses) irrguarwitnesses.value = d.irrguarwitnesses
    if (d.documents) Object.assign(documents, d.documents)
    if (d.maintab) maintab.value = d.maintab
    if (d.perinfostep) perinfostep.value = d.perinfostep
    if (d.irrguarstep) irrguarstep.value = d.irrguarstep
    if (d.savedAt) draftSavedAt.value = d.savedAt
  } catch (e) {
    console.warn('Failed to load draft:', e)
  }
}

const promptClearDraft = () => {
  Swal.fire({
    title: 'Reset Onboarding Form?',
    text: 'This will clear all entered details and start a blank form.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#1A237E',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Reset'
  }).then((res) => {
    if (res.isConfirmed) {
      localStorage.removeItem(STORAGE_KEY)
      window.location.reload()
    }
  })
}

// Final Submit
const submitApplication = async () => {
  saveSig1Data()
  saveSigGuarData()
  saveSigIrrData()

  if (!emp.signature) {
    Swal.fire({
      icon: 'warning',
      title: 'Employee Signature Required',
      text: 'Please sign on Page 4 of the Employee Information Form before submitting.',
      confirmButtonColor: '#1A237E'
    })
    maintab.value = 1
    perinfostep.value = 4
    nextTick(initPage4Canvases)
    return
  }

  if (!agreedDeclaration.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Declaration Required',
      text: 'Please check the box confirming the Melcom Employee Declaration.',
      confirmButtonColor: '#1A237E'
    })
    return
  }

  submitting.value = true

  const payload = {
    emp: {
      ...emp,
      signature: emp.signature,
      guarsignature: emp.guarsignature
    },
    wives: wives.value,
    haschildren: haschildren.value,
    childrens: childrens.value,
    iscurwork: iscurwork.value,
    curwork,
    isunion: isunion.value,
    unioninfo,
    wpermit,
    dlicense,
    econts: econts.value,
    soccont,
    edus: edus.value,
    workexps: workexps.value,
    refs: refs.value,
    nominee,
    banksocial,
    guarantor: {
      name: irrguar.guarname,
      relation: irrguar.relation,
      occupation: irrguar.occupation,
      employer: irrguar.company,
      phoneno: irrguar.mobileno,
      address: irrguar.residenceaddr,
      ghcardno: irrguar.ghcard
    },
    irrguar: {
      ...irrguar,
      signature: irrguar.signature
    },
    irrguarwitnesses: irrguarwitnesses.value,
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
      text: 'Your Melcom onboarding application has been successfully submitted to Melcom HR.',
      confirmButtonColor: '#1A237E'
    })
  } catch (error) {
    submitting.value = false
    console.error('Submission error:', error)
    const msg = error.response?.data?.message || 'Failed to submit onboarding form. Please check your data and connection.'
    Swal.fire({
      icon: 'error',
      title: 'Submission Error',
      text: msg,
      confirmButtonColor: '#1A237E'
    })
  }
}

const copyReference = async (ref) => {
  if (!ref) return
  try {
    await navigator.clipboard.writeText(ref)
    copyStatusText.value = 'Copied!'
    setTimeout(() => { copyStatusText.value = 'Copy' }, 2500)
  } catch (e) {
    copyStatusText.value = 'Copied'
  }
}

const printSummary = () => window.print()
const startNewApplication = () => {
  localStorage.removeItem(STORAGE_KEY)
  window.location.reload()
}

// Watchers for canvases initialization
watch([maintab, perinfostep], () => {
  if (maintab.value === 1 && perinfostep.value === 4) {
    nextTick(initPage4Canvases)
  }
})

watch([maintab, irrguarstep], () => {
  if (maintab.value === 3 && irrguarstep.value === 3) {
    nextTick(initIrrCanvas)
  }
})

onMounted(() => {
  loadDraftFromStorage()
  if (maintab.value === 1 && perinfostep.value === 4) {
    nextTick(initPage4Canvases)
  }
})
</script>

<style scoped>
.form-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  color: #334155;
  margin-bottom: 0.35rem;
}

.form-input, .form-select, .form-textarea {
  width: 100%;
  border-radius: 0.75rem;
  border: 1.5px solid #cbd5e1;
  padding: 0.65rem 0.85rem;
  font-size: 0.8125rem;
  color: #0f172a;
  background-color: #ffffff;
  transition: all 0.15s ease-in-out;
  outline: none;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  border-color: #1A237E;
  box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.12);
}

.section-card {
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.section-header {
  background: #f8fafc;
  padding: 0.875rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.8125rem;
  font-weight: 900;
  color: #1e293b;
  letter-spacing: 0.025em;
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.accent-bar {
  width: 4px;
  height: 16px;
  background: #1A237E;
  border-radius: 9999px;
  display: inline-block;
}

.profile-upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 1rem;
  padding: 1.25rem;
  background-color: #f8fafc;
  transition: border-color 0.2s;
}

.profile-upload-zone:hover {
  border-color: #1A237E;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border-radius: 0.875rem;
  font-size: 0.75rem;
  font-weight: 800;
  color: #475569;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  white-space: nowrap;
}

.tab-btn:hover {
  color: #0f172a;
  background-color: rgba(255, 255, 255, 0.6);
}

.activetab {
  background-color: #1A237E !important;
  color: #ffffff !important;
  box-shadow: 0 4px 6px -1px rgba(26, 35, 126, 0.25);
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}
</style>
