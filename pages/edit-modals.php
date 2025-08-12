<!-- Photo Edit Modal -->
<div id="editPhotoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Edit Photo</h3>
            <button onclick="closeModal('editPhotoModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="photo_action" value="update">
            <input type="hidden" id="editPhotoId" name="photo_id">
            
            <div class="mb-4">
                <label for="editPhotoTitle" class="block text-gray-700 mb-2">Title</label>
                <input type="text" id="editPhotoTitle" name="title" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label for="editPhotoDescription" class="block text-gray-700 mb-2">Description</label>
                <textarea id="editPhotoDescription" name="description" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="editPhotoDate" class="block text-gray-700 mb-2">Date Taken</label>
                <input type="date" id="editPhotoDate" name="date_taken" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('editPhotoModal')" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Milestone Edit Modal -->
<div id="editMilestoneModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Edit Milestone</h3>
            <button onclick="closeModal('editMilestoneModal')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="milestone_action" value="update">
            <input type="hidden" id="editMilestoneId" name="milestone_id">
            
            <div class="mb-4">
                <label for="editMilestoneTitle" class="block text-gray-700 mb-2">Title</label>
                <input type="text" id="editMilestoneTitle" name="title" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="mb-4">
                <label for="editMilestoneDescription" class="block text-gray-700 mb-2">Description</label>
                <textarea id="editMilestoneDescription" name="description" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="editMilestoneDate" class="block text-gray-700 mb-2">Date Achieved</label>
                <input type="date" id="editMilestoneDate" name="date_achieved" class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('editMilestoneModal')" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>