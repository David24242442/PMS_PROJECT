$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\GoalsView.vue"
$content = Get-Content $path -Raw

# Replace Section I and II using Regex anchor to avoid carriage returns issues
$pattern = '(?s)<!-- Section I: Personnel Information -->.*?<!-- Section III: Quarterly Tracking Summary \(Step 2\) -->'

$replacement = @"
                    <!-- I. Personnel Identification (Screenshot 2 Top Identity) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Candidate Area -->
                        <div class="bg-[#F8FAFC] border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                            <div class="bg-[#1A237E] px-5 py-3 border-b border-[#1A237E]/10 flex justify-between items-center">
                                <h4 class="font-bold text-xs text-white uppercase tracking-wide">Candidate Name</h4>
                                <i class="pi pi-user text-white text-xs"></i>
                            </div>
                            <div class="p-4 space-y-2">
                                <div class="p-3 bg-white border border-slate-100 rounded-lg text-sm font-black text-slate-800">{{ selectedGoal.candidate_name || '---' }}</div>
                                <div class="flex gap-4 text-[10px] font-black text-slate-400">
                                    <span>Dept: {{ selectedGoal.department || 'Operations' }}</span>
                                    <span>Location: {{ selectedGoal.location || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Manager Area -->
                        <div class="bg-[#F8FAFC] border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                            <div class="bg-[#1E3A8A] px-5 py-3 border-b border-[#1E3A8A]/10 flex justify-between items-center">
                                <h4 class="font-bold text-xs text-white uppercase tracking-wide">Line Manager Name</h4>
                                <i class="pi pi-id-card text-white text-xs"></i>
                            </div>
                            <div class="p-4 space-y-2">
                                <div class="p-3 bg-white border border-slate-100 rounded-lg text-sm font-black text-slate-800">{{ selectedGoal.manager_name || 'Administrator' }}</div>
                                <div class="text-[10px] font-black text-slate-400">Authorized Evaluator</div>
                            </div>
                        </div>
                    </div>

                    <!-- II. Strategic Goal Details & SMART Side (Screenshot 2 Main setup) -->
                    <div class="grid grid-cols-3 gap-6 mb-8 items-start">
                        <!-- Left 2 Cols: Goal Cards -->
                        <div class="col-span-2 space-y-6">
                            <!-- GOALS -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#E8EAF6] px-5 py-3 border-b border-[#C5CAE9]">
                                    <h4 class="font-black text-sm text-[#1A237E] uppercase tracking-wide">GOALS</h4>
                                </div>
                                <div class="p-5 space-y-4">
                                     <div>
                                         <label class="block text-[10px] font-black text-[#1A237E] uppercase mb-1">Goals Title</label>
                                         <div class="p-3 bg-[#F8FAFC] border border-slate-100 rounded-lg text-xs font-black text-slate-800">{{ selectedGoal.title }}</div>
                                     </div>
                                     <div class="space-y-2">
                                         <label class="block text-[10px] font-black text-[#3949AB] uppercase mb-1">Goals Description</label>
                                         <div v-for="(desc, index) in parseList(selectedGoal.description)" :key="index" class="flex gap-3">
                                              <div class="w-7 h-7 rounded-lg bg-[#E8EAF6] flex items-center justify-center text-[#1A237E] font-black text-xs border border-[#C5CAE9] shrink-0 mt-0.5">{{ index+1 }}</div>
                                              <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-bold text-slate-700 flex-1">{{ desc }}</div>
                                         </div>
                                     </div>
                                </div>
                            </div>

                            <!-- PURPOSES -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#B2DFDB] px-5 py-3 border-b border-[#80CBC4]">
                                     <h4 class="font-black text-sm text-[#004D40] uppercase tracking-wide">PURPOSES</h4>
                                </div>
                                <div class="p-5 space-y-2">
                                     <div v-for="(p, index) in parseList(selectedGoal.purposes)" :key="index" class="flex gap-3">
                                          <div class="w-7 h-7 rounded-lg bg-[#E0F2F1] text-[#00695C] flex items-center justify-center border border-[#B2DFDB] shrink-0 mt-0.5"><i class="pi pi-check text-[10px] font-black"></i></div>
                                          <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-semibold text-slate-700 flex-1">{{ p }}</div>
                                     </div>
                                </div>
                            </div>

                            <!-- CHALLENGES -->
                            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="bg-[#FFE0B2] px-5 py-3 border-b border-[#FFCC80]">
                                     <h4 class="font-black text-sm text-[#E65100] uppercase tracking-wide">CHALLENGES</h4>
                                </div>
                                <div class="p-5 space-y-2">
                                     <div v-for="(c, index) in parseList(selectedGoal.challenges)" :key="index" class="flex gap-3">
                                          <div class="w-7 h-7 rounded-lg bg-[#FFF3E0] text-[#E65100] flex items-center justify-center border border-[#FFE0B2] shrink-0 mt-0.5"><i class="pi pi-exclamation-triangle text-[10px]"></i></div>
                                          <div class="p-3 bg-white border border-slate-100 rounded-lg text-xs font-semibold text-slate-700 flex-1">{{ c }}</div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right 1 Col: Smart Side Checklist -->
                        <div class="col-span-1">
                             <div class="bg-white rounded-2xl border border-gray-200 shadow-md overflow-hidden sticky top-6">
                                  <div class="bg-[#1A237E] px-4 py-3"><h4 class="font-black text-xs text-white uppercase">MY GOAL IS...</h4></div>
                                  <div class="divide-y divide-gray-100">
                                       <div v-for="criteria in smartLabels" :key="criteria.key" class="flex items-center justify-between px-4 py-3.5" :class="selectedGoal.smart_criteria?.[criteria.key] ? 'bg-green-50/50' : ''">
                                            <span class="font-black text-xs text-slate-700">{{ criteria.label }}</span>
                                            <div class="flex items-center gap-3">
                                                 <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs text-white" :class="criteria.color.split(' ')[0]">{{ criteria.short }}</div>
                                                 <i class="pi" :class="selectedGoal.smart_criteria?.[criteria.key] ? 'pi-check-circle text-green-600 text-sm' : 'pi-circle text-gray-200 text-sm'"></i>
                                            </div>
                                       </div>
                                  </div>
                             </div>
                        </div>
                    </div>

                    <!-- Section III: Quarterly Tracking Summary (Step 2) -->
"@

$newContent = $content -replace $pattern, $replacement
Set-Content $path $newContent -NoNewline
Write-Host "Replaced successfully"
